<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaKeluarga extends Model
{
    use HasFactory;

    protected $table = 'anggota_keluarga';

    protected $fillable = [
        'keluarga_id',
        'nama',
        'nik',
        'hubungan',
        'urutan_anak',
        'pendidikan',
        'pekerjaan',
        'pendapatan',
        'kesehatan',
    ];

    protected $casts = [
        'pendapatan' => 'decimal:2',
        'urutan_anak' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    // Anggota milik satu keluarga
    public function keluarga()
    {
        return $this->belongsTo(Keluarga::class, 'keluarga_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES (OPTIONAL)
    |--------------------------------------------------------------------------
    */

    public function scopeAnak($query)
    {
        return $query->where('hubungan', 'anak');
    }

    public function scopeKepala($query)
    {
        return $query->where('hubungan', 'kepala');
    }

    public function scopeIstri($query)
    {
        return $query->where('hubungan', 'istri');
    }
}
