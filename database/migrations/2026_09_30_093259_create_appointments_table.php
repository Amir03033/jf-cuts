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
        Schema::create('Appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status')->default('scheduled');
            $table->decimal('amount_paid', 8, 2)->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['barbershop_id', 'starts_at']);   // agenda en beschikbaarheid
            $table->index(['customer_id', 'starts_at']);     // klantgeschiedenis
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Appointments');
    }
};
