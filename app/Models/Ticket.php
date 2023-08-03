<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'tickets';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'booking_id',
        'created_at',
    ];

    public function booking() {
        return $this->belongsTo(Booking::class);
    }
}
