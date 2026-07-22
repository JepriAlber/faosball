<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StaffService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    protected function uploadPhoto($file, string $staffCode): string
    {
        $filename = strtoupper($staffCode) . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs(config('faos.upload.staff'), $filename, 'public');
    }

    protected function deletePhoto(?string $photo): void
    {
        if ($photo && Storage::disk('public')->exists($photo)) {
            Storage::disk('public')->delete($photo);
        }
    }

    protected function resolveAcademy(array $data): Academy
    {
        if ($this->academyService->isSuperAdmin()) {

            $academy = Academy::find($data['id_academy'] ?? null);

            if (! $academy) {
                throw new \Exception(__('Academy tidak ditemukan.'));
            }

            return $academy;
        }

        $academy = $this->academyService->current();

        if (! $academy) {
            throw new \Exception(__('Academy tidak ditemukan.'));
        }

        return $academy;
    }

    protected function applyFilters(Builder $query, array $filters, bool $includeStatus = true): void
    {
        if (! empty($filters['search'])) {

            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%")
                    ->orWhere('staff_code', 'like', "%{$search}%");
            });
        }

        // Filter id_academy cuma berguna buat Super Admin -- user academy
        // biasa sudah dibatasi ke 1 academy lewat AcademyScope, jadi filter
        // ini tidak pernah mengubah hasil untuk mereka (aman diabaikan).
        // Pola sama RoleService::applyFilters().
        if (! empty($filters['id_academy'])) {
            $query->where('id_academy', $filters['id_academy']);
        }

        if (! empty($filters['id_employment_type'])) {
            $query->where('id_employment_type', $filters['id_employment_type']);
        }

        if (! empty($filters['id_staff_position'])) {
            $query->where('id_staff_position', $filters['id_staff_position']);
        }

        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if ($includeStatus && isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Staff::with(['academy', 'employmentType', 'position', 'user']);

        $this->applyFilters($query, $filters);

        match ($filters['sort'] ?? 'newest') {
            'name_asc' => $query->orderBy('full_name'),
            'name_desc' => $query->orderByDesc('full_name'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        return $query->paginate(config('faos.pagination.default'));
    }

    public function statusCounts(array $filters = []): array
    {
        $countFor = function (string $status) use ($filters) {

            $query = Staff::query();

            $this->applyFilters($query, $filters, includeStatus: false);

            return $query->where('status', $status)->count();
        };

        return [
            'active' => $countFor('active'),
            'inactive' => $countFor('inactive'),
            'resigned' => $countFor('resigned'),
        ];
    }

    /**
     * Pola generate kode identik PlayerService::generatePlayerCode() --
     * prefix {ACADEMY_CODE}{YY}, 5 digit berurutan, row-lock supaya aman
     * dari race condition saat 2 staff dibuat bersamaan.
     */
    protected function generateStaffCode(Academy $academy): string
    {
        $prefix = strtoupper($academy->code) . now()->format('y');

        return DB::transaction(function () use ($prefix) {

            $last = Staff::withoutGlobalScopes()
                ->where('staff_code', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('staff_code')
                ->first();

            $next = $last ? ((int) substr($last->staff_code, strlen($prefix)) + 1) : 1;

            do {
                $code = $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
                $exists = Staff::withoutGlobalScopes()->where('staff_code', $code)->exists();
                $next++;
            } while ($exists);

            return $code;
        });
    }

    public function create(array $data): Staff
    {
        return DB::transaction(function () use ($data) {

            $academy = $this->resolveAcademy($data);
            $staffCode = $this->generateStaffCode($academy);

            $photo = isset($data['photo']) ? $this->uploadPhoto($data['photo'], $staffCode) : null;

            return Staff::create([
                'id_academy' => $academy->id_academy,
                'id_employment_type' => $data['id_employment_type'],
                'id_staff_position' => $data['id_staff_position'],
                'staff_code' => $staffCode,
                'photo' => $photo,
                'full_name' => $data['full_name'],
                'nickname' => $data['nickname'] ?? null,
                'gender' => $data['gender'],
                'birth_place' => $data['birth_place'],
                'birth_date' => $data['birth_date'],
                'nationality' => $data['nationality'] ?? 'Indonesia',
                'religion' => $data['religion'] ?? null,
                'blood_type' => $data['blood_type'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'join_date' => $data['join_date'] ?? now(),
                'end_date' => $data['end_date'] ?? null,
                'salary' => $data['salary'] ?? null,
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function update(Staff $staff, array $data): Staff
    {
        return DB::transaction(function () use ($staff, $data) {

            $oldPhoto = $staff->photo;
            $photo = $oldPhoto;

            if (isset($data['photo'])) {
                $photo = $this->uploadPhoto($data['photo'], $staff->staff_code);
            }

            $staff->update([
                // id_academy & staff_code sengaja TIDAK ikut diubah.
                'id_employment_type' => $data['id_employment_type'],
                'id_staff_position' => $data['id_staff_position'],
                'photo' => $photo,
                'full_name' => $data['full_name'],
                'nickname' => $data['nickname'] ?? null,
                'gender' => $data['gender'],
                'birth_place' => $data['birth_place'],
                'birth_date' => $data['birth_date'],
                'nationality' => $data['nationality'] ?? 'Indonesia',
                'religion' => $data['religion'] ?? null,
                'blood_type' => $data['blood_type'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'join_date' => $data['join_date'] ?? $staff->join_date,
                'end_date' => $data['end_date'] ?? null,
                'salary' => $data['salary'] ?? null,
                'status' => $data['status'] ?? $staff->status,
                'notes' => $data['notes'] ?? null,
            ]);

            // Hapus foto lama SETELAH update sukses -- pola sama PlayerService.
            if (isset($data['photo']) && $oldPhoto) {
                $this->deletePhoto($oldPhoto);
            }

            return $staff;
        });
    }

    public function delete(Staff $staff): bool
    {
        return DB::transaction(function () use ($staff) {

            $this->deletePhoto($staff->photo);

            // Ikut hapus akun login terkait, kalau ada -- pola sama PlayerService.
            if ($staff->id_user) {
                User::where('id_user', $staff->id_user)->delete();
            }

            return $staff->delete();
        });
    }
}
