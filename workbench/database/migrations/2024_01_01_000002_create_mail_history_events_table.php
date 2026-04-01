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
        Schema::create('mail_history_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreignId('mail_history_id')->constrained('mail_histories')->cascadeOnDelete();
            $table->string('type')->index();
            $table->json('payload')->nullable();
            $table->string('provider')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_history_events');
    }
};
