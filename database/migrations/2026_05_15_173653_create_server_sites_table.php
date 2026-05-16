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
        Schema::create('server_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->unique();
            $table->string('domain')->unique();
            $table->json('aliases')->nullable();
            $table->string('document_root');
            $table->string('system_user')->nullable();
            $table->string('php_fpm_socket')->nullable();
            $table->boolean('ssl_enabled')->default(false);
            $table->string('status')->default('draft')->index();
            $table->timestamp('last_deployed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_sites');
    }
};
