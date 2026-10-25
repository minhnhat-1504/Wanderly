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
        Schema::create('travel_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->json('needs');
            $table->string('pace')->nullable();
            $table->decimal('max_budget', 12, 2)->default(0.00);
            $table->enum('status', ['open', 'bidding', 'fulfilled', 'cancelled'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['trip_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_requests');
    }
};
