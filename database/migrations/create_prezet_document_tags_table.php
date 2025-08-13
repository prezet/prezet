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
        $tableName = Prezet::getTableName('document_tags');
        $documentsTable = Prezet::getTableName('documents');
        $tagsTable = Prezet::getTableName('tags');
        
        Schema::connection($connection)->create($tableName, function (Blueprint $table) use ($documentsTable, $tagsTable) {
            $table->id();
            $table->foreignId('document_id')
                ->index()
                ->constrained($documentsTable);
            $table->foreignId('tag_id')
                ->index()
                ->constrained($tagsTable);
        });
    }

    public function down(): void
    {
        $connection = Prezet::getDatabaseConnection();
        $tableName = Prezet::getTableName('document_tags');
        
        Schema::connection($connection)->dropIfExists($tableName);
    }
};
