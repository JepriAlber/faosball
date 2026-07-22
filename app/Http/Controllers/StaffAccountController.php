<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\StoreStaffAccountRequest;
use App\Http\Requests\Staff\UpdateStaffAccountRequest;
use App\Models\Role;
use App\Models\Staff;
use App\Services\AccountService;
use Illuminate\Support\Facades\DB;

class StaffAccountController extends Controller
{
    protected AccountService $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    public function create(Staff $staff)
    {
        if ($staff->id_user) {
            return redirect()->route('staff.index')->with('error', __('Staff sudah memiliki akun.'));
        }

        return view('staff.account.create', [
            'title' => __('Buat Akun Staff'),
            'staff' => $staff->load('activeContract.position'),
            // Default Role staff position (kalau ada, dari Contract Active) jadi
            // pilihan AWAL di view lewat old('role_id', $staff->activeContract?->position?->role_id)
            // -- bukan dipaksa di sini, admin tetap bisa pilih role lain.
            'roles' => Role::where('id_academy', $staff->id_academy)->orderBy('name')->get(),
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => __('Buat Akun')],
            ],
        ]);
    }

    public function store(StoreStaffAccountRequest $request, Staff $staff)
    {
        try {

            if ($staff->id_user) {
                return redirect()->route('staff.index')->with('error', __('Staff sudah memiliki akun.'));
            }

            DB::transaction(function () use ($request, $staff) {

                $role = Role::findOrFail($request->role_id);

                $user = $this->accountService->create([
                    'id_academy' => $staff->id_academy,
                    'name' => $staff->full_name,
                    'email' => $request->email,
                    'password' => $request->password,
                ], $role);

                $staff->update(['id_user' => $user->id_user]);
            });

            return redirect()->route('staff.index')->with('success', __('Akun staff berhasil dibuat.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal membuat akun staff'));
        }
    }

    public function edit(Staff $staff)
    {
        if (! $staff->user) {
            return redirect()->route('staff.show', $staff)->with('error', __('Staff belum memiliki akun.'));
        }

        return view('staff.account.edit', [
            'title' => __('Edit Akun Staff'),
            'staff' => $staff,
            'user' => $staff->user,
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => $staff->full_name, 'url' => route('staff.show', $staff)],
                ['label' => __('Edit Account')],
            ],
        ]);
    }

    public function update(UpdateStaffAccountRequest $request, Staff $staff)
    {
        try {

            if (! $staff->user) {
                return redirect()->route('staff.show', $staff)->with('error', __('Staff belum memiliki akun.'));
            }

            $this->accountService->update($staff->user, $request->validated());

            return redirect()->route('staff.show', $staff)->with('success', __('Account staff berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal update account'));
        }
    }

    public function password(Staff $staff)
    {
        try {

            if (! $staff->user) {
                return redirect()->route('staff.show', $staff)->with('error', __('Staff belum memiliki akun.'));
            }

            $newPassword = $this->accountService->generatePassword();

            $this->accountService->resetPassword($staff->user, $newPassword);

            return redirect()
                ->route('staff.show', $staff)
                ->with('success', __('Password berhasil direset. Password baru: ') . $newPassword);

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal reset password'), 'staff.show', [$staff]);
        }
    }

    public function status(Staff $staff)
    {
        try {

            if (! $staff->user) {
                return redirect()->route('staff.show', $staff)->with('error', __('Staff belum memiliki akun.'));
            }

            $status = ! $staff->user->status;

            $this->accountService->changeStatus($staff->user, $status);

            return redirect()
                ->route('staff.show', $staff)
                ->with('success', $status
                    ? __('Account staff berhasil diaktifkan.')
                    : __('Account staff berhasil dinonaktifkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal mengubah status account'), 'staff.show', [$staff]);
        }
    }
}
