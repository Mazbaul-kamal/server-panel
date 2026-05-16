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
        Schema::create('database_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('database');
            $table->string('username');
            $table->string('host')->default('localhost');
            $table->json('privileges');
            $table->string('status')->default('draft')->index();
            $table->timestamp('last_provisioned_at')->nullable();
            $table->timestamps();

            $table->unique(['username', 'host']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_accounts');
    }
};
