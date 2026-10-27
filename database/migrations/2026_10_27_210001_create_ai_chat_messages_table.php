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
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->nullable()->constrained('trips')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->text('content');
            $table->json('suggested_action')->nullable();
            $table->boolean('is_applied')->default(false);
            $table->timestamps();

            $table->index(['trip_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
