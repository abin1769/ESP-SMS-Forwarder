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
        Schema::create('sms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('phone', 32);
            $table->text('message');
            $table->timestamp('received_at')->nullable();
            $table->boolean('processed')->default(false);
            $table->timestamps();

            // Indexes for fast searching and filtering
            $table->index('phone');
            $table->index('received_at');
            $table->index('processed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms');
    }
};
