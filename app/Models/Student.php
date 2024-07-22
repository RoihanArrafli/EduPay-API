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
        'SPP'
    ];
// hehe
    protected function file(): Attribute {
        return Attribute::make(
            get: fn($file) => url('/storage/data/siswa/' . $file),
        );
    }
}
