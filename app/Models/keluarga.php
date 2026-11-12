<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keluarga extends Model
{
    use HasFactory;

    protected $table = 'keluarga';

    protected $fillable = [
        'nomor_kk',
        'alamat',
        'rt',
        'rw',
        'rw_id',
        'kepala_keluarga',
        'nik_kepala',
        'total_anggota',
        'total_pendapatan',
        'data_tambahan',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    // Keluarga milik satu RW
    public function rw()
    {
        return $this->belongsTo(Rw::class, 'rw_id');
    }

    // Keluarga memiliki banyak anggota
    public function anggota()
    {
        return $this->hasMany(AnggotaKeluarga::class, 'keluarga_id');
    }
}
