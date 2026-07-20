<?php

namespace App\Http\Controllers;

use App\Http\Requests\Academy\StoreAcademyAccountRequest;
use App\Http\Requests\Academy\UpdateAcademyAccountRequest;
use App\Models\Academy;
use App\Services\AccountService;
use Illuminate\Support\Facades\DB;

class AcademyAccountController extends Controller
{
    protected AccountService $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    public function create(Academy $academy)
    {
        if ($academy->id_owner_user) {
            return redirect()
                ->route('academies.show', $academy)
                ->with('error', __('Academy sudah memiliki akun Owner.'));
        }

        return view('academies.account.create', [
            'title' => __('Buat Akun Owner'),
            'academy' => $academy,
            'breadcrumb' => [
                ['label' => __('Manajemen Academy'), 'url' => route('academies.index')],
                ['label' => $academy->name, 'url' => route('academies.show', $academy)],
                ['label' => __('Buat Akun Owner')],
            ],
        ]);
    }

    public function store(StoreAcademyAccountRequest $request, Academy $academy)
    {
        try {

            if ($academy->id_owner_user) {
                return redirect()
                    ->route('academies.show', $academy)
                    ->with('error', __('Academy sudah memiliki akun Owner.'));
            }

            DB::transaction(function () use ($request, $academy) {

                $user = $this->accountService->create([
                    'id_academy' => $academy->id_academy,
                    'name' => $academy->name,
                    'email' => $request->email,
                    'password' => $request->password,
                ], 'Owner');

                $academy->update([
                    'id_owner_user' => $user->id_user,
                ]);
            });

            return redirect()
                ->route('academies.show', $academy)
                ->with('success', __('Akun Owner berhasil dibuat.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal membuat akun Owner'));
        }
    }

    public function edit(Academy $academy)
    {
        if (!$academy->owner) {
            return redirect()
                ->route('academies.show', $academy)
                ->with('error', __('Academy belum memiliki akun Owner.'));
        }

        return view('academies.account.edit', [
            'title' => __('Edit Akun Owner'),
            'academy' => $academy,
            'user' => $academy->owner,
            'breadcrumb' => [
                ['label' => __('Manajemen Academy'), 'url' => route('academies.index')],
                ['label' => $academy->name, 'url' => route('academies.show', $academy)],
                ['label' => __('Edit Akun Owner')],
            ],
        ]);
    }

    public function update(UpdateAcademyAccountRequest $request, Academy $academy)
    {
        try {

            if (!$academy->owner) {
                return redirect()
                    ->route('academies.show', $academy)
                    ->with('error', __('Academy belum memiliki akun Owner.'));
            }

            $this->accountService->update(
                $academy->owner,
                $request->validated()
            );

            return redirect()
                ->route('academies.show', $academy)
                ->with('success', __('Akun Owner berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal update akun Owner'));
        }
    }

    public function password(Academy $academy)
    {
        try {

            if (!$academy->owner) {
                return redirect()
                    ->route('academies.show', $academy)
                    ->with('error', __('Academy belum memiliki akun Owner.'));
            }

            $newPassword = $this->accountService->generatePassword();

            $this->accountService->resetPassword(
                $academy->owner,
                $newPassword
            );

            return redirect()
                ->route('academies.show', $academy)
                ->with('success', __('Password berhasil direset. Password baru: ') . $newPassword);

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal reset password'), 'academies.show', [$academy]);
        }
    }

    public function status(Academy $academy)
    {
        try {

            if (!$academy->owner) {
                return redirect()
                    ->route('academies.show', $academy)
                    ->with('error', __('Academy belum memiliki akun Owner.'));
            }

            $status = !$academy->owner->status;

            $this->accountService->changeStatus(
                $academy->owner,
                $status
            );

            return redirect()
                ->route('academies.show', $academy)
                ->with(
                    'success',
                    $status
                        ? __('Akun Owner berhasil diaktifkan.')
                        : __('Akun Owner berhasil dinonaktifkan.')
                );

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal mengubah status akun Owner'), 'academies.show', [$academy]);
        }
    }
}
