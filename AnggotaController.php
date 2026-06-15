<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Anggota;
use App\Http\Requests\StoreAnggotaRequest;
use App\Http\Requests\UpdateAnggotaRequest;
use App\Exports\AnggotaExport;
use Maatwebsite\Excel\Facades\Excel;

class AnggotaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $anggotas = Anggota::latest()->get();

        $totalAnggota = Anggota::count();
        $anggotaAktif = Anggota::where('status', 'Aktif')->count();
        $anggotaNonaktif = Anggota::where('status', 'Nonaktif')->count();

        return view('anggota.index', compact(
            'anggotas',
            'totalAnggota',
            'anggotaAktif',
            'anggotaNonaktif'
        ));
    }

    public function search(Request $request)
{
    $query = Anggota::query();

    if ($request->keyword) {
        $query->where(function ($q) use ($request) {
            $q->where('nama', 'like', '%' . $request->keyword . '%')
                ->orWhere('email', 'like', '%' . $request->keyword . '%')
                ->orWhere('telepon', 'like', '%' . $request->keyword . '%')
                ->orWhere('kode_anggota', 'like', '%' . $request->keyword . '%');
        });
    }

    if ($request->jenis_kelamin) {
        $query->where('jenis_kelamin', $request->jenis_kelamin);
    }

    if ($request->status) {
        $query->where('status', $request->status);
    }

    if ($request->kode_anggota) {
        $query->where('kode_anggota', 'like', '%' . $request->kode_anggota . '%');
    }

    $anggotas = $query->latest()->get();

    $totalAnggota = $anggotas->count();
    $anggotaAktif = $anggotas->where('status', 'Aktif')->count();
    $anggotaNonaktif = $anggotas->where('status', 'Nonaktif')->count();

    return view('anggota.index', compact(
        'anggotas',
        'totalAnggota',
        'anggotaAktif',
        'anggotaNonaktif'
    ));
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $anggota = Anggota::findOrFail($id);

        return view('anggota.show', compact('anggota'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $kodeAnggota = $this->generateKodeAnggota();

        return view('anggota.create', compact('kodeAnggota'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAnggotaRequest $request)
    {
        try {
            $validated = $request->validated();

            // Generate kode anggota otomatis
            $validated['kode_anggota'] = $this->generateKodeAnggota();

            // Simpan data anggota
            Anggota::create($validated);

            return redirect()
                ->route('anggota.index')
                ->with('success', 'Data anggota berhasil ditambahkan');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan anggota: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $anggota = Anggota::findOrFail($id);

        return view('anggota.edit', compact('anggota'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAnggotaRequest $request, string $id)
    {
        try {
            $anggota = Anggota::findOrFail($id);

            $anggota->update($request->validated());

            return redirect()
                ->route('anggota.show', $anggota->id)
                ->with('success', 'Data anggota berhasil diupdate!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal mengupdate anggota: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $anggota = Anggota::findOrFail($id);
            $namaAnggota = $anggota->nama;

            $anggota->delete();

            return redirect()
                ->route('anggota.index')
                ->with('success', "Anggota '{$namaAnggota}' berhasil dihapus!");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus anggota: ' . $e->getMessage());
        }
    }

    /**
     * Generate kode anggota otomatis.
     */
    private function generateKodeAnggota()
    {
        $tahun = date('Y');

        $lastAnggota = Anggota::whereYear('created_at', $tahun)
            ->orderBy('kode_anggota', 'desc')
            ->first();

        if ($lastAnggota) {
            $lastNumber = intval(substr($lastAnggota->kode_anggota, -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'AGT-' . $tahun . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
    
    public function export()
    {
        return Excel::download(
        new AnggotaExport,
        'anggota_' . date('Y-m-d_His') . '.xlsx'
        );
    }
}