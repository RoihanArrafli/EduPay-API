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
        'kelas_id',
        'tagihan_spp',
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

    // public function pembayarans() {
    //     return $this->hasMany(Pembayaran::class);
    // }

    // protected static function boot() {
    //     parent::boot();

    //     static::creating(function ($student) {
    //         $kelas = Kelas::find($student->kelas_id);
    //         if ($kelas) {
    //             $student->tagihan_spp = $kelas->nominal_spp;
    //         }
    //     });

    //     static::updating(function ($student) {
    //         $kelas = Kelas::find($student->kelas_id);
    //         if ($kelas) {
    //             $student->tagihan_spp = $kelas->nominal_spp;
    //         }
    //     });
    // }
}
// hehe
