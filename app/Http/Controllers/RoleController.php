<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\RoleFormRequest;
use App\Services\RoleService;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
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
            'roles'=>$this->roleService->paginate()
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
            'permissionGroups'=>$this->roleService->permissionGroups()
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
    ]);
}

    /**
     * Update the specified resource.
     */
    public function update(RoleFormRequest $request,Role $role)
    {
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