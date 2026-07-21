<?php

namespace App\Http\Controllers;

use App\Http\Requests\Academy\AcademyFormRequest;
use App\Models\Academy;
use App\Services\AcademyManagementService;
use Illuminate\Http\Request;

class AcademyController extends Controller
{
    protected AcademyManagementService $academyManagementService;


    public function __construct( AcademyManagementService $academyManagementService )
    {
        $this->academyManagementService = $academyManagementService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'status', 'sort']));

        return view('academies.index',[
            'title'=>__('Manajemen Academy'),
            'breadcrumb'=>[
                [
                    'label'=>__('Manajemen Academy')
                ]
            ],
            'academies' => $this->academyManagementService->paginate($filters),
            'statusCounts' => $this->academyManagementService->statusCounts($filters),
            'filters' => $filters,
            'subscriptionTypes' => AcademyManagementService::SUBSCRIPTION_TYPES,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('academies.create',[
            'title'=>__('Tambah Academy'),
            'breadcrumb'=>[
                [
                    'label'=>__('Manajemen Academy'),
                    'url'=>route('academies.index')
                ],
                [
                    'label'=>__('Tambah Academy')
                ]
            ],
            'subscriptionTypes' => AcademyManagementService::SUBSCRIPTION_TYPES,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(AcademyFormRequest $request)
    {
        try {

            $this->academyManagementService->create(  $request->validated() );


            return redirect()
                ->route('academies.index')
                ->with(
                    'success',
                    __('Academy berhasil ditambahkan.')
                );

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menambahkan academy'));
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Academy $academy)
    {
        $academy->load('owner');

        return view('academies.show',[
            'title'=>__('Detail Academy'),
            'breadcrumb'=>[
                [
                    'label'=>__('Manajemen Academy'),
                    'url'=>route('academies.index')
                ],
                [
                    'label'=>__('Detail Academy')
                ]
            ],
            'academy'=>$academy,
            'subscriptionTypes' => AcademyManagementService::SUBSCRIPTION_TYPES,
            'subscriptionStatus' => $this->academyManagementService->subscriptionStatus($academy),
        ]);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Academy $academy)
    {
        return view('academies.edit',[
            'title'=>__('Edit Academy'),
            'breadcrumb'=>[
                [
                    'label'=>__('Manajemen Academy'),
                    'url'=>route('academies.index')
                ],
                [
                    'label'=>__('Edit Academy')
                ]
            ],
            'academy'=>$academy,
            'subscriptionTypes' => AcademyManagementService::SUBSCRIPTION_TYPES,
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(  AcademyFormRequest $request, Academy $academy ) {

        try {

            $this->academyManagementService->update(
                $academy,
                $request->validated()
            );


            return redirect()
                ->route('academies.index')
                ->with(
                    'success',
                    __('Academy berhasil diperbarui.')
                );


        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal memperbarui academy'));
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Academy $academy)
    {
        try {

            $this->academyManagementService->delete(
                $academy
            );


            return redirect()
                ->route('academies.index')
                ->with(
                    'success',
                    __('Academy berhasil dihapus.')
                );


        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghapus academy'), 'academies.index');
        }
    }
}