<?php

namespace App\Services;

use App\Models\Academy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AcademyManagementService
{

    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
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
     * Generate academy slug
     */
    protected function generateSlug(string $name): string
    {
        return Str::slug($name);
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
                $data['logo'] = $this->uploadLogo(
                    $data['logo'],
                    $data['code']
                );
            }

            $academy = Academy::create($data);

            $this->roleService->createDefaultRoles($academy);

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

                $this->deleteLogo($academy->logo);

                $data['logo'] = $this->uploadLogo(
                    $data['logo'],
                    $data['code']
                );
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

            $this->deleteLogo($academy->logo);

            return $academy->delete();
        });
    }


}