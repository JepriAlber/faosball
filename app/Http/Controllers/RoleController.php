<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\RoleFormRequest;
use App\Models\Academy;
use App\Models\Role;
use App\Services\AcademyService;
use App\Services\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected RoleService $roleService;
    protected AcademyService $academyService;

    public function __construct(RoleService $roleService, AcademyService $academyService)
    {
        $this->roleService = $roleService;
        $this->academyService = $academyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'id_academy', 'sort']));

        return view('roles.index',[
            'title'=>__('Role Management'),
            'breadcrumb'=>[
                ['label'=>__('Administration')],
                ['label'=>__('Role Management')]
            ],
            'roles'=>$this->roleService->paginate($filters),
            'filters'=>$filters,
            'isSuperAdmin'=>$this->academyService->isSuperAdmin(),
            // Opsi dropdown filter Academy -- cuma dibutuhkan Super Admin,
            // yang melihat role lintas seluruh academy.
            'academies'=>$this->academyService->isSuperAdmin()
                ? Academy::orderBy('name')->get()
                : collect(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('roles.create',[
            'title'=>__('Tambah Role'),
            'breadcrumb'=>[
                [
                    'label'=>__('Role Management'),
                    'url'=>route('roles.index')
                ],
                [
                    'label'=>__('Tambah Role')
                ]
            ],
            'permissionGroups'=>$this->roleService->permissionGroups(),
            'isSuperAdmin'=>$this->academyService->isSuperAdmin(),
            'academies'=>$this->academyService->isSuperAdmin()
                ? Academy::orderBy('name')->get()
                : collect(),
        ]);
    }

    /**
     * Store a newly created resource.
     */
    public function store(RoleFormRequest $request)
    {
        try{

            $this->roleService->create(
                $request->validated()
            );

            return redirect()
                ->route('roles.index')
                ->with('success',__('Role berhasil ditambahkan.'));

        }catch(\Exception $e){

            return $this->handleException($e, __('Gagal menambahkan role'));

        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        $this->authorize('view', $role);

        $data = $this->roleService->detail($role);

        return view('roles.show', [
            'title' => __('Detail Role'),
            'breadcrumb' => [
                [
                    'label' => __('Role Management'),
                    'url' => route('roles.index'),
                ],
                [
                    'label' => __('Detail Role'),
                ],
            ],
            'role' => $data['role'],
            'permissionGroups' => $data['permissionGroups'],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $this->authorize('update', $role);

        return view('roles.edit', [
            'title' => __('Edit Role'),
            'breadcrumb' => [
                [
                    'label' => __('Role Management'),
                    'url' => route('roles.index'),
                ],
                [
                    'label' => __('Edit Role'),
                ],
            ],
            'role' => $role,
            'permissionGroups' => $this->roleService->permissionGroups(),
            'selectedPermissions' => $role->permissions->pluck('name')->toArray(),
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'academies' => $this->academyService->isSuperAdmin()
                ? Academy::orderBy('name')->get()
                : collect(),
        ]);
    }

    /**
     * Update the specified resource.
     */
    public function update(RoleFormRequest $request, Role $role)
    {
        $this->authorize('update', $role);

        try{

            $this->roleService->update(
                $role,
                $request->validated()
            );

            return redirect()
                ->route('roles.index')
                ->with('success',__('Role berhasil diperbarui.'));

        }catch(\Exception $e){

            return $this->handleException($e, __('Gagal memperbarui role'));

        }
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        try{

            $this->roleService->delete($role);

            return redirect()
                ->route('roles.index')
                ->with('success',__('Role berhasil dihapus.'));

        }catch(\Exception $e){

            return $this->handleException($e, __('Gagal menghapus role'), 'roles.index');

        }
    }
}
