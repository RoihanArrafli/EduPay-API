<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'alamat',
        'jenis_kelamin',
        'ortu',
        'TTL',
        'kelas',
        'nis'
    ];
// hehe
    protected function file(): Attribute {
        return Attribute::make(
            get: fn($file) => url('/storage/data/siswa/' . $file),
        );
    }

    public function kelas() {
        return $this->belongsTo(Kelas::class);
    }

    protected static function boot() {
        parent::boot();

        static::creating(function ($student) {
            $kelas = Kelas::find($student->kelas_id);
            if ($kelas) {
                $student->nis = $kelas->nominal_spp;
            }
        });

        static::updating(function ($student) {
            $kelas = Kelas::find($student->kelas_id);
            if ($kelas) {
                $student->nis = $kelas->nominal_spp;
            }
        });
    }
}
// hehe
