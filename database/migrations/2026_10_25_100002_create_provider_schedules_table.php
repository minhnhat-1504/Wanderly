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
        if (! Schema::hasTable('provider_schedules')) {
            Schema::create('provider_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
                $table->date('blocked_date');
                $table->string('reason')->nullable();
                $table->timestamps();

                $table->unique(['provider_id', 'blocked_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_schedules');
    }
};
