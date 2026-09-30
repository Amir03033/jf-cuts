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
        Schema::create('barbershop_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('booking_interval')->default(30);   // minuten
            $table->unsignedSmallInteger('max_booking_days')->default(30);   // dagen vooruit
            $table->unsignedSmallInteger('cancellation_limit')->default(60); // minuten voor aanvang
            $table->unsignedSmallInteger('reschedule_limit')->default(60);   // minuten voor aanvang
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barbershop_settings');
    }
};
