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
        'note'
    ];

    public function setPending() {
        $this->attributes['status'] = 'pending';
        self::save();
    }

    public function setSuccess() {
        $this->attributes['status'] = 'success';
        self::save();
    }

    public function setFailed() {
        $this->attributes['status'] = 'failed';
        self::save();
    }

    public function setExpired() {
        $this->attributes['status'] = 'expired';
        self::save();
    }
}
