<?php

namespace App\Http\Controllers\API;

use App\Imports\StudentImport;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function index() {
        $students = Student::first()->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'List Data Siswa',
            'data' => $students
        ], 200);
    }
// hehe
    public function addStudent(Request $request) {
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'alamat' => 'required',
            'jenis_kelamin' => 'required|in:laki-laki,perempuan',
            'ortu' => 'required',
            'TTL' => 'required',
            'kelas' => 'required',
            'SPP' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada Kesalahan!',
                'data' => $validator->errors()
            ], 401);
        }

        $data = Student::create([
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'jenis_kelamin' => $request->jenis_kelamin,
            'ortu' => $request->ortu,
            'TTL' => $request->TTL,
            'kelas' => $request->kelas,
            'SPP' => $request->SPP
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil ditambahkan!',
            'data' => $data
        ], 201);
    }

    public function importStudents(Request $request) {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $file = $request->file('file');
        $file->storeAs('public/data/student');

        $import = Excel::import(new StudentImport, $file);

        return response()->json([
            'success' => true,
            'message' => 'data berhasil diimpor',
            'data' => $import
        ], 201);
    }

    public function showStudent($id) {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data Student tidak ditemukan',
                'data' => null
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data siswa ditemukan!',
            'data' => $student
        ], 200);
    }

    public function updateStudent(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'alamat' => 'required',
            'jenis_kelamin' => 'required|in:laki-laki,perempuan',
            'ortu' => 'required',
            'TTL' => 'required',
            'kelas' => 'required',
            'SPP' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada kesalahan!',
                'data' => $validator->errors()
            ], 422);
        }

        $student = Student::find($id);

        if(!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data student tidak ditemukan',
            ], 404);
        }

        $student->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data student berhasil diupdate',
            'data' => $student
        ], 201);
    }

    public function deleteStudent($id) {
        $student = Student::find($id);

        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus!',
            'data' => null
        ], 200);
    }
}