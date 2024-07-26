<?php

use App\Models\Kelas;
use App\Models\Student;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('update:tagihan_spp', function () {
    $students = Student::all();
    foreach ($students as $student) {
        $kelas = Kelas::find($student->kelas_id);

        $student->tagihan_spp += $kelas->nominal_spp;
        $student->save();
    }

    $this->info('Tagihan SPP bulan ini berhasil di update');
})->description('Tagihan SPP berhasil diperbarui untuk semua siswa')->monthlyOn(1, '00:00');
