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
            $table->boolean('is_suspended')->default(false)->after('remember_token');
            $table->string('suspended_reason', 500)->nullable()->after('is_suspended');
            $table->timestamp('suspended_at')->nullable()->after('suspended_reason');
            $table->softDeletes()->after('updated_at');

            $table->index(['role', 'is_suspended']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'is_suspended']);
            $table->dropColumn(['is_suspended', 'suspended_reason', 'suspended_at', 'deleted_at']);
        });
    }
};
