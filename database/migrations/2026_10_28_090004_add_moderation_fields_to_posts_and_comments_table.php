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
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('comments_count');
            $table->timestamp('hidden_at')->nullable()->after('is_hidden');
            $table->string('hidden_reason', 500)->nullable()->after('hidden_at');
            $table->softDeletes()->after('updated_at');

            $table->index('is_hidden');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('content');
            $table->timestamp('hidden_at')->nullable()->after('is_hidden');
            $table->string('hidden_reason', 500)->nullable()->after('hidden_at');
            $table->softDeletes()->after('updated_at');

            $table->index('is_hidden');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['is_hidden']);
            $table->dropColumn(['is_hidden', 'hidden_at', 'hidden_reason', 'deleted_at']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['is_hidden']);
            $table->dropColumn(['is_hidden', 'hidden_at', 'hidden_reason', 'deleted_at']);
        });
    }
};
