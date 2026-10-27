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
        Schema::create('proposal_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->json('pros')->nullable();
            $table->json('cons')->nullable();
            $table->json('hidden_costs')->nullable();
            $table->text('summary_verdict')->nullable();
            $table->unsignedTinyInteger('score')->default(85);
            $table->string('badge', 50)->nullable(); // 'best_value', 'budget_saver', 'premium_choice'
            $table->json('suggested_combos')->nullable();
            $table->timestamps();

            $table->unique('proposal_id');
            $table->index('badge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_insights');
    }
};
