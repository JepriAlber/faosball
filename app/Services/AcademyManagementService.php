<?php

namespace App\Services;

use App\Models\Academy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;

class AcademyManagementService
{
    /**
     * Tipe subscription yang valid. Key disimpan di kolom subscription_type,
     * value adalah label tampilan. Dipakai bersama oleh Form Request
     * (validasi) dan Controller (populate dropdown + label tampilan) supaya
     * daftarnya tidak dobel-tulis di banyak tempat.
     */
    public const SUBSCRIPTION_TYPES = [
        'monthly' => 'Bulanan',
        'yearly' => 'Tahunan',
    ];

    /**
     * Ambang hari sebelum subscription_ends_at yang dianggap "Akan Berakhir".
     */
    protected const SUBSCRIPTION_EXPIRING_SOON_DAYS = 7;

    protected RoleService $roleService;
    protected PlayerTypeService $playerTypeService;
    protected PlayerCategoryService $playerCategoryService;
    protected AccountService $accountService;

    public function __construct(
        RoleService $roleService,
        PlayerTypeService $playerTypeService,
        PlayerCategoryService $playerCategoryService,
        AccountService $accountService
    ) {
        $this->roleService = $roleService;
        $this->playerTypeService = $playerTypeService;
        $this->playerCategoryService = $playerCategoryService;
        $this->accountService = $accountService;
    }

    /**
     * Upload academy logo
     */
    protected function uploadLogo($file, string $academyCode): string
    {
        $filename = strtoupper($academyCode)
            . '-'
            . Str::uuid()
            . '.'
            . $file->getClientOriginalExtension();


        return $file->storeAs(
            'academies/logo',
            $filename,
            'public'
        );
    }


    /**
     * Delete academy logo
     */
    protected function deleteLogo(?string $logo): void
    {
        if ($logo && Storage::disk('public')->exists($logo)) {
            Storage::disk('public')->delete($logo);
        }
    }


    /**
     * Hapus file logo asli + kedua variannya (kalau ada). Dipakai saat logo
     * diganti (create ulang tidak butuh ini, cuma update/updateProfile/delete).
     */
    protected function deleteLogoVariants(Academy $academy): void
    {
        $this->deleteLogo($academy->logo);
        $this->deleteLogo($academy->logo_sidebar);
        $this->deleteLogo($academy->logo_favicon);
    }


    /**
     * Batas bounding box varian logo_sidebar -- muat di dalam kotak ini, jaga
     * aspect ratio, TANPA crop (scaleDown). Proporsi lebar (245x65) meniru
     * bentuk logo sistem yang sekarang (wordmark lebar), supaya logo academy
     * apapun bentuknya tidak dipaksa jadi kotak kecil yang terasa mengecil
     * sendiri di slot sidebar/header.
     */
    protected const LOGO_SIDEBAR_MAX_WIDTH = 245;
    protected const LOGO_SIDEBAR_MAX_HEIGHT = 65;


    /**
     * Generate 2 varian ukuran dari file logo yang baru di-upload.
     *
     * - logo_sidebar : scaleDown ke bounding box 245x65 (jaga aspect ratio,
     *   TANPA crop) -- lihat issue3.md Bagian 4.1 untuk riwayat keputusan ini.
     * - logo_favicon : cover ke 64x64 (crop persegi, center) -- favicon browser
     *   WAJIB persegi.
     *
     * SVG di-skip (bukan error) -- driver GD tidak bisa membaca vektor. Kegagalan
     * apapun di sini ditangkap, TIDAK BOLEH menggagalkan create/update Academy
     * yang memanggilnya. Lihat issue3.md Bagian 4.2.
     */
    protected function generateLogoVariants($file, string $academyCode): array
    {
        if (strtolower($file->getClientOriginalExtension()) === 'svg') {
            return [
                'logo_sidebar' => null,
                'logo_favicon' => null,
            ];
        }

        try {

            $manager = new ImageManager(new Driver());
            $image = $manager->decodePath($file->getRealPath());

            $sidebarPath = 'academies/logo/' . strtoupper($academyCode) . '-' . Str::uuid() . '-sidebar.png';
            $faviconPath = 'academies/logo/' . strtoupper($academyCode) . '-' . Str::uuid() . '-favicon.png';

            // WAJIB clone -- Intervention Image memutasi objek in-place, resize
            // kedua tanpa clone akan dijalankan di atas hasil resize pertama.
            Storage::disk('public')->put(
                $sidebarPath,
                (string) (clone $image)->scaleDown(
                    self::LOGO_SIDEBAR_MAX_WIDTH,
                    self::LOGO_SIDEBAR_MAX_HEIGHT
                )->encode(new PngEncoder())
            );

            Storage::disk('public')->put(
                $faviconPath,
                (string) (clone $image)->cover(64, 64)->encode(new PngEncoder())
            );

            return [
                'logo_sidebar' => $sidebarPath,
                'logo_favicon' => $faviconPath,
            ];

        } catch (\Throwable $e) {

            Log::warning('Gagal generate varian logo academy', [
                'academy_code' => $academyCode,
                'exception' => $e->getMessage(),
            ]);

            return [
                'logo_sidebar' => null,
                'logo_favicon' => null,
            ];
        }
    }


    /**
     * Upload logo asli + generate variannya sekaligus, dipakai bersama oleh
     * create()/update()/updateProfile() supaya logic-nya tidak tertulis 3 kali.
     */
    protected function processLogoUpload($file, string $academyCode): array
    {
        return array_merge(
            ['logo' => $this->uploadLogo($file, $academyCode)],
            $this->generateLogoVariants($file, $academyCode)
        );
    }


    /**
     * Generate academy slug
     */
    protected function generateSlug(string $name): string
    {
        return Str::slug($name);
    }


    /*
    |--------------------------------------------------------------------------
    | List / Filter Academy
    |--------------------------------------------------------------------------
    */

    /**
     * Terapkan filter search/status ke query.
     *
     * $includeStatus = false dipakai oleh statusCounts() -- hitungan tiap tab
     * status tidak boleh ikut kefilter oleh status tab yang sedang aktif,
     * supaya angkanya tetap utuh saat user pindah tab. Sama seperti pola di
     * PlayerService::applyFilters().
     */
    protected function applyFilters(Builder $query, array $filters, bool $includeStatus = true): void
    {
        if (!empty($filters['search'])) {

            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($includeStatus && isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status'] === 'active');
        }
    }

    /**
     * Daftar academy untuk halaman index, dengan search/filter/sort.
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Academy::query();

        $this->applyFilters($query, $filters);

        match ($filters['sort'] ?? 'newest') {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        $academies = $query->paginate(config('faos.pagination.default'));

        // subscription_status dihitung di sini (Service), BUKAN di Blade,
        // supaya Blade cuma menampilkan, tidak menghitung. Ditempel sebagai
        // atribut dinamis (bukan kolom asli), aman dipakai read-only di view.
        $academies->getCollection()->each(function (Academy $academy) {
            $academy->subscription_status = $this->subscriptionStatus($academy);
        });

        return $academies;
    }

    /**
     * Jumlah academy per status, untuk badge di tabs halaman index.
     *
     * Cuma dua state (boolean), jadi cukup dua query where()->count() --
     * tidak perlu groupBy seperti PlayerService::statusCounts() yang punya
     * 4 nilai enum.
     */
    public function statusCounts(array $filters = []): array
    {
        $countFor = function (bool $status) use ($filters) {

            $query = Academy::query();

            $this->applyFilters($query, $filters, includeStatus: false);

            return $query->where('status', $status)->count();
        };

        return [
            'active' => $countFor(true),
            'inactive' => $countFor(false),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Subscription
    |--------------------------------------------------------------------------
    */

    /**
     * Hitung status subscription academy dari subscription_ends_at.
     *
     * TIDAK disimpan sebagai kolom terpisah -- dihitung ulang tiap dipanggil,
     * supaya tidak ada risiko data basi (kolom status yang lupa di-refresh
     * saat tanggal berjalan). Lihat issue.md Bagian 4.1.
     *
     * @return string 'belum_diatur' | 'aktif' | 'akan_berakhir' | 'kadaluarsa'
     */
    public function subscriptionStatus(Academy $academy): string
    {
        if (! $academy->subscription_ends_at) {
            return 'belum_diatur';
        }

        // Parameter FALSE di argumen kedua WAJIB -- tanpa itu hasilnya selalu
        // absolut/positif, academy yang sudah kadaluarsa akan terbaca
        // "akan berakhir" alih-alih "kadaluarsa". Lihat issue.md Bagian 4.2.
        $daysLeft = now()->startOfDay()->diffInDays($academy->subscription_ends_at, false);

        return match (true) {
            $daysLeft < 0 => 'kadaluarsa',
            $daysLeft <= self::SUBSCRIPTION_EXPIRING_SOON_DAYS => 'akan_berakhir',
            default => 'aktif',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Profile (Self-Service Owner)
    |--------------------------------------------------------------------------
    */

    /**
     * Update field profil UMUM academy (dipakai self-service Owner lewat
     * AcademyProfileController). SENGAJA membangun payload dengan whitelist
     * eksplisit -- bukan mengoper $data mentah ke Model::update() -- supaya
     * code/status/subscription_* TIDAK PERNAH bisa lolos lewat method ini,
     * apapun yang terjadi di Form Request nanti. Lihat issue.md Bagian 4.7.
     */
    public function updateProfile(Academy $academy, array $data): Academy
    {
        return DB::transaction(function () use ($academy, $data) {

            $payload = [
                'name' => $data['name'],
                'tagline' => $data['tagline'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'],
                'description' => $data['description'] ?? null,
            ];

            if (isset($data['logo'])) {
                $this->deleteLogoVariants($academy);
                $payload = array_merge($payload, $this->processLogoUpload($data['logo'], $academy->code));
            }

            $academy->update($payload);

            return $academy;
        });
    }


    /**
     * Create academy
     */
    public function create(array $data): Academy
    {
        return DB::transaction(function () use ($data) {

            $data['code'] = strtoupper($data['code']);
            $data['slug'] = $this->generateSlug($data['name']);
            $data['status'] = $data['status'] ?? false;

            if (isset($data['logo'])) {
                $data = array_merge($data, $this->processLogoUpload($data['logo'], $data['code']));
            }

            $academy = Academy::create($data);

            $this->roleService->createDefaultRoles($academy);
            $this->playerTypeService->createDefaultPlayerTypes($academy);
            $this->playerCategoryService->createDefaultPlayerCategories($academy);

            if (!empty($data['create_account'])) {

                $owner = $this->accountService->create([
                    'id_academy' => $academy->id_academy,
                    'name' => $academy->name,
                    'email' => $data['owner_email'],
                    'password' => $data['owner_password'],
                ], 'Owner');

                $academy->update([
                    'id_owner_user' => $owner->id_user,
                ]);
            }

            return $academy;
        });
    }


    /**
     * Update academy
     */
    public function update(Academy $academy, array $data): Academy
    {
        return DB::transaction(function () use ($academy, $data) {

            $data['code'] = strtoupper($data['code']);
            $data['slug'] = $this->generateSlug($data['name']);
            $data['status'] = $data['status'] ?? false;

            if (isset($data['logo'])) {
                $this->deleteLogoVariants($academy);
                $data = array_merge($data, $this->processLogoUpload($data['logo'], $data['code']));
            }

            $academy->update($data);

            return $academy;
        });
    }


    /**
     * Delete academy
     */
    public function delete(Academy $academy): bool
    {
        return DB::transaction(function () use ($academy) {

            $this->deleteLogoVariants($academy);

            return $academy->delete();
        });
    }


}