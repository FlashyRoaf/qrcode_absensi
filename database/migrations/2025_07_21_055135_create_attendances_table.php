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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('qrcode');
            // $table->string('shift');
            // $table->string('division');
            $table->date('date');
            // $table->enum('status', ['present', 'absent', 'late'])->default('present');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->float('distance')->nullable();
            // $table->string('location')->nullable(); // Optional location field
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
