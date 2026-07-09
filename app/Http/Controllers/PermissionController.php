<?php

namespace App\Http\Controllers;

use App\Http\Requests\Permission\PermissionFormRequest;
use App\Services\PermissionService;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('permissions.index', [
            'title' => 'Permission Management',
            'breadcrumb' => [
                ['label' => 'Administration'],
                ['label' => 'Permission Management'],
            ],
            'permissions' => $this->permissionService->paginate(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('permissions.create', [
            'title' => 'Tambah Permission',
            'breadcrumb' => [
                [
                    'label' => 'Permission Management',
                    'url' => route('permissions.index'),
                ],
                [
                    'label' => 'Tambah Permission',
                ],
            ],
            'modules' => $this->permissionService->existingModules(),
            'actions' => $this->permissionService->actionOptions(),
        ]);
    }

    /**
     * Store a newly created resource.
     */
    public function store(PermissionFormRequest $request)
    {
        try {

            $this->permissionService->create(
                $request->validated()
            );

            return redirect()
                ->route('permissions.index')
                ->with('success', 'Permission berhasil ditambahkan.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal menambahkan permission');

        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        $data = $this->permissionService->detail($permission);

        return view('permissions.show', [
            'title' => 'Detail Permission',
            'breadcrumb' => [
                [
                    'label' => 'Permission Management',
                    'url' => route('permissions.index'),
                ],
                [
                    'label' => 'Detail Permission',
                ],
            ],
            'permission' => $data['permission'],
            'presenter' => $data['presenter'],
            'roles' => $data['roles'],
        ]);
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Permission $permission)
    {
        try {

            $this->permissionService->delete($permission);

            return redirect()
                ->route('permissions.index')
                ->with('success', 'Permission berhasil dihapus.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal menghapus permission', 'permissions.index');

        }
    }
}
