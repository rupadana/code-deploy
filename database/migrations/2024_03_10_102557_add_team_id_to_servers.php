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
        Schema::table('servers', function (Blueprint $table) {
            $table->integer('team_id')->nullable();
        });
        Schema::table('sites', function (Blueprint $table) {
            $table->integer('team_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('team_id');
        });
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('team_id');
        });
    }
};
