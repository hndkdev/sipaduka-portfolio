<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('keluarga', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kk')->unique();
            $table->text('alamat');
            $table->string('rt', 5);
            $table->string('rw', 5);

            $table->foreignId('rw_id')
                  ->constrained('rw')
                  ->cascadeOnDelete();

            $table->string('kepala_keluarga');
            $table->string('nik_kepala');
            $table->integer('total_anggota');
            $table->decimal('total_pendapatan', 15, 2)->default(0);
            $table->text('data_tambahan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keluarga');
    }
};
