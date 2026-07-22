<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmploymentType\EmploymentTypeFormRequest;
use App\Models\Academy;
use App\Models\EmploymentType;
use App\Services\AcademyService;
use App\Services\EmploymentTypeService;

class EmploymentTypeController extends Controller
{
    protected EmploymentTypeService $employmentTypeService;
    protected AcademyService $academyService;

    public function __construct(EmploymentTypeService $employmentTypeService, AcademyService $academyService)
    {
        $this->employmentTypeService = $employmentTypeService;
        $this->academyService = $academyService;
    }

    public function index()
    {
        return view('employment-types.index', [
            'title' => __('Employment Type'),
            'breadcrumb' => [
                ['label' => __('Office')],
                ['label' => __('Employment Type')],
            ],
            'employmentTypes' => $this->employmentTypeService->paginate(),
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function create()
    {
        return view('employment-types.create', [
            'title' => __('Tambah Employment Type'),
            'breadcrumb' => [
                ['label' => __('Employment Type'), 'url' => route('employment-types.index')],
                ['label' => __('Tambah Employment Type')],
            ],
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'academies' => $this->academyService->isSuperAdmin()
                ? Academy::orderBy('name')->get()
                : collect(),
        ]);
    }

    public function store(EmploymentTypeFormRequest $request)
    {
        try {

            $this->employmentTypeService->create($request->validated());

            return redirect()
                ->route('employment-types.index')
                ->with('success', __('Employment type berhasil ditambahkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menambahkan employment type'));
        }
    }

    public function edit(EmploymentType $employmentType)
    {
        return view('employment-types.edit', [
            'title' => __('Edit Employment Type'),
            'breadcrumb' => [
                ['label' => __('Employment Type'), 'url' => route('employment-types.index')],
                ['label' => __('Edit Employment Type')],
            ],
            'employmentType' => $employmentType,
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function update(EmploymentTypeFormRequest $request, EmploymentType $employmentType)
    {
        try {

            $this->employmentTypeService->update($employmentType, $request->validated());

            return redirect()
                ->route('employment-types.index')
                ->with('success', __('Employment type berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal memperbarui employment type'));
        }
    }

    public function destroy(EmploymentType $employmentType)
    {
        try {

            $this->employmentTypeService->delete($employmentType);

            return redirect()
                ->route('employment-types.index')
                ->with('success', __('Employment type berhasil dihapus.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghapus employment type'), 'employment-types.index');
        }
    }
}
