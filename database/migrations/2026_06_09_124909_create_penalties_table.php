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
        Schema::create('penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weekly_report_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'uploaded', 'approved', 'rejected', 'exempted'])->default('pending');
            $table->string('proof_path')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'weekly_report_id']);
        });

        Schema::create('penalty_exempt_weeks', function (Blueprint $table) {
            $table->id();
            $table->date('week_start'); // Minggu yang dikecualikan (tidak kena hukuman)
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalties');
        Schema::dropIfExists('penalty_exempt_weeks');
    }
};
