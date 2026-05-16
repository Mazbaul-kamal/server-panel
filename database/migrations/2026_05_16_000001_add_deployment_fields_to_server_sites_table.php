<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_sites', function (Blueprint $table) {
            $table->string('git_repository')->nullable()->after('ssl_enabled');
            $table->string('git_branch')->default('main')->after('git_repository');
            $table->string('git_deploy_key_path')->nullable()->after('git_branch');
            $table->timestamp('last_git_deployed_at')->nullable()->after('last_deployed_at');
            $table->timestamp('last_file_uploaded_at')->nullable()->after('last_git_deployed_at');
        });
    }

    public function down(): void
    {
        Schema::table('server_sites', function (Blueprint $table) {
            $table->dropColumn([
                'git_repository',
                'git_branch',
                'git_deploy_key_path',
                'last_git_deployed_at',
                'last_file_uploaded_at',
            ]);
        });
    }
};
