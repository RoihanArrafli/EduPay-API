<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
    //     $kelas = Kelas::where('tingkat_kelas', $row['kelas'])->first();
    //     $tagihan_spp = $kelas ? $kelas->nominal_spp : 0;
        $kelas = Kelas::find($row['kelas_id']);
        return new Student([
            'nama' => $row['nama'],
            'alamat' => $row['alamat'],
            'jenis_kelamin' => $row['jenis_kelamin'],
            'ortu' => $row['ortu'],
            'TTL' => $row['ttl'],
            'kelas_id' => $row['kelas_id'],
            'kelas' => $kelas->tingkat_kelas,
            // 'kelas_id' => $kelas ? $kelas->id : null,
            'tagihan_spp' => $kelas->nominal_spp,
        ]);
    }

    // hehe
}
