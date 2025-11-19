<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))->create(config('activitylog.table_name'), function (Blueprint $blueprint): void {
            $blueprint->bigIncrements('id');
            $blueprint->string('log_name')->nullable();
            $blueprint->text('description');
            $blueprint->nullableMorphs('subject', 'subject');
            $blueprint->nullableMorphs('causer', 'causer');
            $blueprint->json('properties')->nullable();
            $blueprint->timestamps();
            $blueprint->index('log_name');
        });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))->dropIfExists(config('activitylog.table_name'));
    }
};
