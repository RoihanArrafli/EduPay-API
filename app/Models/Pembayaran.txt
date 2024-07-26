<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;

    protected $fillable = [
        'donor_name',
        'donor_email',
        'donation_type',
        'amount',
        'student_id',
        'note'
    ];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function setPending() {
        $this->attributes['status'] = 'pending';
        $this->save();
    }

    public function setSuccess() {
        $this->attributes['status'] = 'success';
        $this->save();
    }

    public function setFailed() {
        $this->attributes['status'] = 'failed';
        $this->save();
    }

    public function setExpired() {
        $this->attributes['status'] = 'expired';
        $this->save();
    }
}
