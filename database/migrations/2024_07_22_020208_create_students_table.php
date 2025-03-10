<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('nis');
            $table->string('nama');
            $table->string('alamat');
            $table->enum('jenis_kelamin', ['laki-laki', 'perempuan']);
            $table->string('ortu');
            $table->string('TTL');
            $table->foreignId('kelas_id')->constrained('kelas')->onDelete('cascade');
            $table->string('kelas');
            $table->double('tagihan_spp')->nullable();
            $table->timestamps();
        });
    }
    //hehe

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
