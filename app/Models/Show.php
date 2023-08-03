<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Show extends Model
{
    use HasFactory;

    protected $table = 'shows';
    public $timestamps = false;
    protected $hidden = [
        'concert_id',
    ];

    protected $fillable = [
        'concert_id',
        'start',
        'end',
    ];

    public function concert() {
        return $this->belongsTo(Concert::class);
    }
}
