<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rw', function (Blueprint $table) {
            // Cek apakah kolom nomor_rw sudah ada
            if (!Schema::hasColumn('rw', 'nomor_rw')) {
                $table->string('nomor_rw')->nullable();  // Tanpa after()
            }
        });
    }

    public function down()
    {
        Schema::table('rw', function (Blueprint $table) {
            $table->dropColumn('nomor_rw');
        });
    }
};