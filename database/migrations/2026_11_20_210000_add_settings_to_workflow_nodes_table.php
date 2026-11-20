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
        Schema::table('workflow_nodes', function (Blueprint $table) {
            // Free-form, type-specific configuration for the node (e.g. the
            // prompt chosen on a prompt node). Each node type decides what it
            // stores and validates here.
            $table->json('settings')->nullable()->after('error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_nodes', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
