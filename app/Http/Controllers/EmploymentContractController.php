<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmploymentContract\StoreEmploymentContractRequest;
use App\Http\Requests\EmploymentContract\UpdateEmploymentContractRequest;
use App\Models\EmploymentContract;
use App\Models\Staff;
use App\Services\EmploymentContractService;
use App\Services\EmploymentTypeService;
use App\Services\StaffPositionService;

class EmploymentContractController extends Controller
{
    protected EmploymentContractService $employmentContractService;
    protected EmploymentTypeService $employmentTypeService;
    protected StaffPositionService $staffPositionService;

    public function __construct(
        EmploymentContractService $employmentContractService,
        EmploymentTypeService $employmentTypeService,
        StaffPositionService $staffPositionService
    ) {
        $this->employmentContractService = $employmentContractService;
        $this->employmentTypeService = $employmentTypeService;
        $this->staffPositionService = $staffPositionService;
    }

    public function create(Staff $staff)
    {
        return view('staff.contracts.create', [
            'title' => __('Buat Kontrak Baru'),
            'staff' => $staff,
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => $staff->full_name, 'url' => route('staff.show', $staff)],
                ['label' => __('Buat Kontrak Baru')],
            ],
            'employmentTypes' => $this->employmentTypeService->selectable($staff->id_academy),
            'staffPositions' => $this->staffPositionService->selectable($staff->id_academy),
            'canViewSalary' => auth()->user()->can('salary.view'),
        ]);
    }

    public function store(StoreEmploymentContractRequest $request, Staff $staff)
    {
        try {

            $this->employmentContractService->createDraft($staff, $request->validated());

            return redirect()
                ->route('staff.show', $staff)
                ->with('success', __('Kontrak baru berhasil dibuat sebagai Draft.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal membuat kontrak'));
        }
    }

    public function edit(Staff $staff, EmploymentContract $contract)
    {
        return view('staff.contracts.edit', [
            'title' => __('Edit Kontrak'),
            'staff' => $staff,
            'contract' => $contract,
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => $staff->full_name, 'url' => route('staff.show', $staff)],
                ['label' => __('Edit Kontrak')],
            ],
            'employmentTypes' => $this->employmentTypeService->selectable($staff->id_academy, $contract->id_employment_type),
            'staffPositions' => $this->staffPositionService->selectable($staff->id_academy, $contract->id_staff_position),
            'canViewSalary' => auth()->user()->can('viewSalary', $staff),
        ]);
    }

    public function update(UpdateEmploymentContractRequest $request, Staff $staff, EmploymentContract $contract)
    {
        try {

            $this->employmentContractService->updateDraft($contract, $request->validated());

            return redirect()
                ->route('staff.show', $staff)
                ->with('success', __('Kontrak berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal memperbarui kontrak'), 'staff.show', [$staff]);
        }
    }

    public function activate(Staff $staff, EmploymentContract $contract)
    {
        try {

            $this->employmentContractService->activate($contract);

            return redirect()->route('staff.show', $staff)->with('success', __('Kontrak berhasil diaktifkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal mengaktifkan kontrak'), 'staff.show', [$staff]);
        }
    }

    public function complete(Staff $staff, EmploymentContract $contract)
    {
        try {

            $this->employmentContractService->complete($contract);

            return redirect()->route('staff.show', $staff)->with('success', __('Kontrak ditandai selesai.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menyelesaikan kontrak'), 'staff.show', [$staff]);
        }
    }

    public function terminate(Staff $staff, EmploymentContract $contract)
    {
        try {

            $this->employmentContractService->terminate($contract);

            return redirect()->route('staff.show', $staff)->with('success', __('Kontrak berhasil dihentikan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghentikan kontrak'), 'staff.show', [$staff]);
        }
    }

    public function cancel(Staff $staff, EmploymentContract $contract)
    {
        try {

            $this->employmentContractService->cancel($contract);

            return redirect()->route('staff.show', $staff)->with('success', __('Kontrak Draft berhasil dibatalkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal membatalkan kontrak'), 'staff.show', [$staff]);
        }
    }
}
