<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Prezet\Prezet\Prezet;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = Prezet::getDatabaseConnection();
        $tableName = Prezet::getTableName('tags');
        
        Schema::connection($connection)->create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('name')
                ->unique();
        });
    }

    public function down(): void
    {
        $connection = Prezet::getDatabaseConnection();
        $tableName = Prezet::getTableName('tags');
        
        Schema::connection($connection)->dropIfExists($tableName);
    }
};
