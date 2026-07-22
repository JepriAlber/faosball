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
        'one_season' => 'Satu Musim',
    ];

    /**
     * Ambang hari sebelum subscription_ends_at yang dianggap "Akan Berakhir".
     */
    protected const SUBSCRIPTION_EXPIRING_SOON_DAYS = 7;

    protected RoleService $roleService;
    protected PlayerTypeService $playerTypeService;
    protected PlayerCategoryService $playerCategoryService;
    protected EmploymentTypeService $employmentTypeService;
    protected StaffPositionService $staffPositionService;
    protected AccountService $accountService;
    protected StaffService $staffService;

    public function __construct(
        RoleService $roleService,
        PlayerTypeService $playerTypeService,
        PlayerCategoryService $playerCategoryService,
        EmploymentTypeService $employmentTypeService,
        StaffPositionService $staffPositionService,
        AccountService $accountService,
        StaffService $staffService
    ) {
        $this->roleService = $roleService;
        $this->playerTypeService = $playerTypeService;
        $this->playerCategoryService = $playerCategoryService;
        $this->employmentTypeService = $employmentTypeService;
        $this->staffPositionService = $staffPositionService;
        $this->accountService = $accountService;
        $this->staffService = $staffService;
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
     * Hapus logo persegi + logo_favicon (dipakai saat field `logo` diganti
     * atau academy dihapus). TERPISAH dari deleteSidebarLogoAsset() --
     * mengganti logo persegi TIDAK BOLEH ikut menghapus logo_sidebar yang
     * mungkin tidak sedang diganti pada request yang sama.
     */
    protected function deleteSquareLogoAssets(Academy $academy): void
    {
        $this->deleteLogo($academy->logo);
        $this->deleteLogo($academy->logo_favicon);
    }


    /**
     * Hapus logo_sidebar saja (dipakai saat field itu diganti atau academy
     * dihapus). TERPISAH dari deleteSquareLogoAssets(), lihat di atas.
     */
    protected function deleteSidebarLogoAsset(Academy $academy): void
    {
        $this->deleteLogo($academy->logo_sidebar);
    }


    /**
     * Batas bounding box logo_sidebar -- muat di dalam kotak ini, jaga aspect
     * ratio, TANPA crop tambahan (scaleDown). Proporsi lebar (245x65) meniru
     * bentuk logo sistem yang sekarang (wordmark lebar). Sejak issue8.md,
     * logo_sidebar punya upload+crop rasio lebar SENDIRI (bukan turunan dari
     * logo persegi lagi) -- konstanta ini sekarang jadi bounding box output
     * untuk hasil crop itu.
     */
    protected const LOGO_SIDEBAR_MAX_WIDTH = 245;
    protected const LOGO_SIDEBAR_MAX_HEIGHT = 65;


    /**
     * Generate varian logo_favicon (cover 64x64, crop persegi) dari file
     * logo PERSEGI yang baru di-upload. Dulu method ini juga menurunkan
     * logo_sidebar dari sumber yang sama -- sejak issue8.md, logo_sidebar
     * punya upload+crop sendiri (lihat processSidebarLogoUpload() di bawah)
     * supaya logo persegi (dipakai kartu nama/kop surat nanti) tidak
     * dipaksa proporsi lebar yang jelek saat di-scaleDown ke slot sidebar.
     *
     * SVG di-skip (return null) -- driver GD tidak bisa membaca vektor.
     * Dalam praktiknya modal crop (logo-crop-field.js) selalu meng-flatten
     * hasil crop ke PNG sebelum submit, jadi cabang ini nyaris tidak pernah
     * kena lewat UI normal -- tetap dijaga untuk submit non-JS/manual.
     */
    protected function generateFaviconVariant($file, string $academyCode): ?string
    {
        if (strtolower($file->getClientOriginalExtension()) === 'svg') {
            return null;
        }

        try {

            $manager = new ImageManager(new Driver());
            $image = $manager->decodePath($file->getRealPath());

            $faviconPath = 'academies/logo/' . strtoupper($academyCode) . '-' . Str::uuid() . '-favicon.png';

            Storage::disk('public')->put(
                $faviconPath,
                (string) $image->cover(64, 64)->encode(new PngEncoder())
            );

            return $faviconPath;

        } catch (\Throwable $e) {

            Log::warning('Gagal generate varian favicon logo academy', [
                'academy_code' => $academyCode,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }


    /**
     * Upload logo_sidebar -- UPLOAD+CROP SENDIRI (rasio lebar, terpisah dari
     * logo persegi), bukan turunan otomatis lagi. scaleDown ke bounding box
     * 245x65 (jaga aspect ratio, TANPA crop tambahan -- user sudah crop
     * rasio yang benar di client lewat <x-logo-upload-field :aspect-ratio="...">).
     *
     * SVG di-skip, alasan sama seperti generateFaviconVariant().
     */
    protected function processSidebarLogoUpload($file, string $academyCode): array
    {
        if (strtolower($file->getClientOriginalExtension()) === 'svg') {
            return ['logo_sidebar' => null];
        }

        try {

            $manager = new ImageManager(new Driver());
            $image = $manager->decodePath($file->getRealPath());

            $sidebarPath = 'academies/logo/' . strtoupper($academyCode) . '-' . Str::uuid() . '-sidebar.png';

            Storage::disk('public')->put(
                $sidebarPath,
                (string) $image->scaleDown(
                    self::LOGO_SIDEBAR_MAX_WIDTH,
                    self::LOGO_SIDEBAR_MAX_HEIGHT
                )->encode(new PngEncoder())
            );

            return ['logo_sidebar' => $sidebarPath];

        } catch (\Throwable $e) {

            Log::warning('Gagal upload logo_sidebar academy', [
                'academy_code' => $academyCode,
                'exception' => $e->getMessage(),
            ]);

            return ['logo_sidebar' => null];
        }
    }


    /**
     * Upload logo PERSEGI asli + generate favicon-nya sekaligus, dipakai
     * bersama oleh create()/update()/updateProfile() untuk field `logo`.
     */
    protected function processLogoUpload($file, string $academyCode): array
    {
        return [
            'logo' => $this->uploadLogo($file, $academyCode),
            'logo_favicon' => $this->generateFaviconVariant($file, $academyCode),
        ];
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
                'primary_color' => $data['primary_color'],
            ];

            if (isset($data['logo'])) {
                $this->deleteSquareLogoAssets($academy);
                $payload = array_merge($payload, $this->processLogoUpload($data['logo'], $academy->code));
            }

            if (isset($data['logo_sidebar'])) {
                $this->deleteSidebarLogoAsset($academy);
                $payload = array_merge($payload, $this->processSidebarLogoUpload($data['logo_sidebar'], $academy->code));
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

            if (isset($data['logo_sidebar'])) {
                $data = array_merge($data, $this->processSidebarLogoUpload($data['logo_sidebar'], $data['code']));
            }

            $academy = Academy::create($data);

            $this->roleService->createDefaultRoles($academy);
            $this->playerTypeService->createDefaultPlayerTypes($academy);
            $this->playerCategoryService->createDefaultPlayerCategories($academy);
            $this->employmentTypeService->createDefaultEmploymentTypes($academy);
            $this->staffPositionService->createDefaultStaffPositions($academy);

            if (!empty($data['create_account'])) {

                $owner = $this->accountService->create([
                    'id_academy' => $academy->id_academy,
                    'name' => $data['owner_full_name'],
                    'email' => $data['owner_email'],
                    'password' => $data['owner_password'],
                ], 'Owner');

                $this->staffService->createForOwner($academy, $owner, [
                    'full_name' => $data['owner_full_name'],
                    'gender' => $data['owner_gender'],
                    'birth_place' => $data['owner_birth_place'],
                    'birth_date' => $data['owner_birth_date'],
                    'phone' => $data['owner_phone'],
                ]);

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
                $this->deleteSquareLogoAssets($academy);
                $data = array_merge($data, $this->processLogoUpload($data['logo'], $data['code']));
            }

            if (isset($data['logo_sidebar'])) {
                $this->deleteSidebarLogoAsset($academy);
                $data = array_merge($data, $this->processSidebarLogoUpload($data['logo_sidebar'], $data['code']));
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

            $this->deleteSquareLogoAssets($academy);
            $this->deleteSidebarLogoAsset($academy);

            return $academy->delete();
        });
    }


}