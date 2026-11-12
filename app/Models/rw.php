<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class rw extends Model
{
    use HasFactory;

    protected $table = 'rw';

    protected $fillable = [
        'nama',
        'nomor_rw',
        'userid',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }

    public function keluarga()
    {
        return $this->hasMany(Keluarga::class, 'rw_id');
    }
}
