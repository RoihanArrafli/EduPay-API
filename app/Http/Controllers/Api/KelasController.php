<?php

namespace App\Http\Controllers\API;

use App\Models\Kelas;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class KelasController extends Controller
{
    public function index()
    {
        $kelas = Kelas::all();

        return response()->json([
            'success' => true,
            'message' => 'List Data Kelas',
            'data' => $kelas
        ]);
    }

    public function addKelas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tingkat_kelas' => 'required',
            'nominal_spp' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada kesalahan!',
                'data' => $validator->errors()
            ]);
        }

        $kelas = Kelas::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data kelas berhasil ditambahkan',
            'data' => $kelas
        ], 201);
    }

    public function showKelas($id)
    {
        $kelas = Kelas::find($id);

        if (!$kelas) {
            return response()->json([
                'success' => false,
                'message' => 'Data kelas tidak ditemukan',
                'data' => null
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail Data kelas',
            'data' => $kelas
        ]);
    }

    public function updateKelas(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tingkat_kelas' => 'required',
            'nominal_spp' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada kesalahan!',
                'data' => $validator->errors()
            ]);
        }

        $kelas = Kelas::find($id);
        $kelas->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data kelas berhasil diubah',
            'data' => $kelas
        ]);
    }

    public function deleteKelas($id) {
        $kelas = Kelas::find($id);

        if (!$kelas) {
            return response()->json([
                'success' => false,
                'message' => 'Data kelas tidak ditemukan',
                'data' => null
            ], 401);
        }

        $kelas->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data kelas berhasil dihapus',
            'data' => null
        ], 200);
    }
}
