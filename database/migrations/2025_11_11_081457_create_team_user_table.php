<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_user', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('team_id');
            $blueprint->foreignId('user_id');
            $blueprint->string('role')->nullable();
            $blueprint->timestamps();

            $blueprint->unique(['team_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_user');
    }
};
