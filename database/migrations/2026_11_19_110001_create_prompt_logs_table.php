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
        Schema::create('prompt_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_session_id')->constrained()->cascadeOnDelete();
            $table->text('prompt');
            $table->longText('response')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['agent_session_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompt_logs');
    }
};
