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
            // The node's last run output, shown on the canvas. The log node
            // stores the parameters it received here (including an agent's
            // response) so the result is visible without opening the log file.
            $table->longText('output')->nullable()->after('settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_nodes', function (Blueprint $table) {
            $table->dropColumn('output');
        });
    }
};
