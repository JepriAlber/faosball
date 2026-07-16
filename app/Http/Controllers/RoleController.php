<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\RoleFormRequest;
use App\Models\Academy;
use App\Models\Role;
use App\Services\AcademyService;
use App\Services\RoleService;

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
    public function index()
    {
        return view('roles.index',[
            'title'=>'Role Management',
            'breadcrumb'=>[
                ['label'=>'Administration'],
                ['label'=>'Role Management']
            ],
            'roles'=>$this->roleService->paginate(),
            'isSuperAdmin'=>$this->academyService->isSuperAdmin(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('roles.create',[
            'title'=>'Tambah Role',
            'breadcrumb'=>[
                [
                    'label'=>'Role Management',
                    'url'=>route('roles.index')
                ],
                [
                    'label'=>'Tambah Role'
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
                ->with('success','Role berhasil ditambahkan.');

        }catch(\Exception $e){

            return $this->handleException($e, 'Gagal menambahkan role');

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
            'title' => 'Detail Role',
            'breadcrumb' => [
                [
                    'label' => 'Role Management',
                    'url' => route('roles.index'),
                ],
                [
                    'label' => 'Detail Role',
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
            'title' => 'Edit Role',
            'breadcrumb' => [
                [
                    'label' => 'Role Management',
                    'url' => route('roles.index'),
                ],
                [
                    'label' => 'Edit Role',
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
                ->with('success','Role berhasil diperbarui.');

        }catch(\Exception $e){

            return $this->handleException($e, 'Gagal memperbarui role');

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
                ->with('success','Role berhasil dihapus.');

        }catch(\Exception $e){

            return $this->handleException($e, 'Gagal menghapus role', 'roles.index');

        }
    }
}
