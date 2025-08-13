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
        $tableName = Prezet::getTableName('documents');

        Schema::connection($connection)->create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('key')->index()->nullable()->unique();
            $table->string('slug')->index()->unique();
            $table->string('filepath')->index()->unique();
            $table->string('category')->index()->nullable();
            $table->string('content_type')->index();
            $table->boolean('draft')->default(false)->index();
            $table->char('hash', length: 32)->index()->unique();
            $table->jsonb('frontmatter');
            $table->timestampTz('created_at')->index();
            $table->timestampTz('updated_at')->index();

            $table->index('filepath', 'hash');
        });
    }

    public function down(): void
    {
        $connection = Prezet::getDatabaseConnection();
        $tableName = Prezet::getTableName('documents');

        Schema::connection($connection)->dropIfExists($tableName);
    }
};
