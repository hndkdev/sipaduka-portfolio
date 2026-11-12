<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Gunakan raw SQL untuk mengubah kolom menjadi nullable tanpa membutuhkan doctrine/dbal
        DB::statement("ALTER TABLE `rw` MODIFY `nomor_rw` VARCHAR(255) NULL;");
    }

    public function down()
    {
        // Kembalikan ke NOT NULL (jika ingin mengembalikan, pastikan tidak ada nilai NULL terlebih dahulu)
        DB::statement("ALTER TABLE `rw` MODIFY `nomor_rw` VARCHAR(255) NOT NULL;");
    }
};
