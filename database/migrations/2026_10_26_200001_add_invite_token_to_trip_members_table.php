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
        Schema::table('trip_members', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('invite_token')->nullable()->unique()->after('role');
            $table->dateTime('token_expires_at')->nullable()->after('invite_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_members', function (Blueprint $table) {
            $table->dropColumn(['invite_token', 'token_expires_at']);
        });
    }
};
