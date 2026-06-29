<?php

namespace App\Http\Controllers;

use App\Http\Requests\academy\AcademyFormRequest;
use App\Models\Academy;
use App\Services\AcademyManagementService;

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
    public function index()
    {
        return view('academies.index',[
            'title'=>'Manajemen Academy',
            'breadcrumb'=>[
                [
                    'label'=>'Manajemen Academy'
                ]
            ],
            'academies'=>Academy::latest()->paginate(10)
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('academies.create',[
            'title'=>'Tambah Academy',
            'breadcrumb'=>[
                [
                    'label'=>'Manajemen Academy',
                    'url'=>route('academies.index')
                ],
                [
                    'label'=>'Tambah Academy'
                ]
            ]
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
                    'Academy berhasil ditambahkan.'
                );

        } catch (\Exception $e) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menambahkan academy: '.$e->getMessage()
                );
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Academy $academy)
    {
        return view('academies.show',[
            'title'=>'Detail Academy',
            'breadcrumb'=>[
                [
                    'label'=>'Manajemen Academy',
                    'url'=>route('academies.index')
                ],
                [
                    'label'=>'Detail Academy'
                ]
            ],
            'academy'=>$academy
        ]);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Academy $academy)
    {
        return view('academies.edit',[
            'title'=>'Edit Academy',
            'breadcrumb'=>[
                [
                    'label'=>'Manajemen Academy',
                    'url'=>route('academies.index')
                ],
                [
                    'label'=>'Edit Academy'
                ]
            ],
            'academy'=>$academy
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
                    'Academy berhasil diperbarui.'
                );


        } catch (\Exception $e) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal memperbarui academy: '.$e->getMessage()
                );
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
                    'Academy berhasil dihapus.'
                );


        } catch (\Exception $e) {

            return redirect()
                ->route('academies.index')
                ->with(
                    'error',
                    'Gagal menghapus academy: '.$e->getMessage()
                );
        }
    }
}