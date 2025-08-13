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
        $tableName = Prezet::getTableName('headings');
        $documentsTable = Prezet::getTableName('documents');
        
        Schema::connection($connection)->create($tableName, function (Blueprint $table) use ($documentsTable) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained($documentsTable)
                ->onDelete('cascade');
            $table->string('text');
            $table->unsignedTinyInteger('level');
            $table->string('section');
        });
    }

    public function down(): void
    {
        $connection = Prezet::getDatabaseConnection();
        $tableName = Prezet::getTableName('headings');
        
        Schema::connection($connection)->dropIfExists($tableName);
    }
};
