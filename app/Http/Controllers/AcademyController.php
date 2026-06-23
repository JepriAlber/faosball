<?php

namespace App\Http\Controllers;

use App\Http\Requests\academy\AcademyFormRequest;
use App\Models\Academy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AcademyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $title = 'Manajemen Academy';
        $academies = Academy::latest()->paginate(10);

        return view('academy.index', compact('academies', 'title'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $title = 'Tambah Academy';
        return view('academy.create', compact('title'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AcademyFormRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Generate slug
            $validated['slug'] = Str::slug($validated['name']);

            // Set status (checkbox/toggle fallback)
            $validated['status'] = $request->has('status') ? (bool) $request->input('status') : false;

            // Handle file upload
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('academies/logo', $filename, 'public');
                $validated['logo'] = $path;
            }

            Academy::create($validated);

            DB::commit();

            return redirect()->route('academy.index')->with('success', 'Academy berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            // Delete uploaded file if transaction fails
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }

            return back()->withInput()->with('error', 'Gagal menambahkan academy: '.$e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $title = 'Detail Academy';
        $academy = Academy::findOrFail($id);

        return view('academy.show', compact('academy', 'title'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {   
        $title = 'Edit Academy';
        $academy = Academy::findOrFail($id);

        return view('academy.edit', compact('academy', 'title'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AcademyFormRequest $request, string $id)
    {
        $academy = Academy::findOrFail($id);
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $validated['slug'] = Str::slug($validated['name']);
            $validated['status'] = $request->has('status') ? (bool) $request->input('status') : false;

            if ($request->hasFile('logo')) {
                // Delete old logo
                if ($academy->logo) {
                    Storage::disk('public')->delete($academy->logo);
                }

                // Upload new logo
                $file = $request->file('logo');
                $filename = time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('academies/logo', $filename, 'public');
                $validated['logo'] = $path;
            }

            $academy->update($validated);

            DB::commit();

            return redirect()->route('academy.index')->with('success', 'Academy berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }

            return back()->withInput()->with('error', 'Gagal memperbarui academy: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $academy = Academy::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($academy->logo) {
                Storage::disk('public')->delete($academy->logo);
            }

            $academy->delete();

            DB::commit();

            return redirect()->route('academy.index')->with('success', 'Academy berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('academy.index')->with('error', 'Gagal menghapus academy: '.$e->getMessage());
        }
    }
}
