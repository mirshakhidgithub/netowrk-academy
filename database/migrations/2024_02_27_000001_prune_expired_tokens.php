<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create a command to prune expired tokens
        // This can be run via: php artisan command:name
        // Or scheduled in: app/Console/Kernel.php
    }

    public function down(): void
    {
        //
    }
};
