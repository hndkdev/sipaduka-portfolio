<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('anggota_keluarga', function (Blueprint $table) {
            $table->id();

            $table->foreignId('keluarga_id')
                  ->constrained('keluarga')
                  ->cascadeOnDelete();

            $table->string('nama');
            $table->string('nik')->nullable();
            $table->enum('hubungan', ['kepala', 'istri', 'anak']);
            $table->unsignedTinyInteger('urutan_anak')->nullable();

            $table->string('pendidikan')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->decimal('pendapatan', 15, 2)->default(0);
            $table->string('kesehatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_keluarga');
    }
};
