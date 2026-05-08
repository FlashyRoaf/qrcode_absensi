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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
            $table->string('phone')->nullable()->after('email');
            $table->string('role')->default('anggota');
            $table->string('device_id')->nullable();
            $table->timestamp('admin_assigned_at')->nullable();
            $table->string('identifier')->nullable();
            $table->boolean('is_blocked')->default(false);
            // $table->string('shift');
            // $table->string('division');
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
            $table->dropColumn('phone');
            $table->dropColumn('role');
            $table->dropColumn('device_id');
            $table->dropColumn('admin_assigned_at');
            // $table->dropColumn('role');
            // $table->dropColumn('shift');
            // $table->dropColumn('division');
            //
        });
    }
};
