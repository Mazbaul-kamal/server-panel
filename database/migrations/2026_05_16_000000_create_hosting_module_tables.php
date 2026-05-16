<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_modules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('category')->index();
            $table->string('status')->default('available')->index();
            $table->boolean('enabled')->default(false);
            $table->text('description')->nullable();
            $table->json('packages')->nullable();
            $table->json('services')->nullable();
            $table->json('ports')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('system_services', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('display_name');
            $table->string('unit')->unique();
            $table->string('package')->nullable();
            $table->string('status')->default('unknown')->index();
            $table->boolean('enabled')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('dns_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('domain')->unique();
            $table->string('status')->default('draft')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dns_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 16);
            $table->text('content');
            $table->unsignedInteger('ttl')->default(3600);
            $table->unsignedSmallInteger('priority')->nullable();
            $table->timestamps();
        });

        Schema::create('ftp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('username')->unique();
            $table->string('root_path');
            $table->string('status')->default('draft')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('last_provisioned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mail_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('domain')->unique();
            $table->string('status')->default('draft')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('last_provisioned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mail_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_domain_id')->constrained()->cascadeOnDelete();
            $table->string('local_part');
            $table->unsignedInteger('quota_mb')->default(1024);
            $table->string('status')->default('draft')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('last_provisioned_at')->nullable();
            $table->timestamps();
            $table->unique(['mail_domain_id', 'local_part']);
        });

        Schema::create('firewall_rules', function (Blueprint $table) {
            $table->id();
            $table->string('action', 16);
            $table->string('protocol', 8)->default('tcp');
            $table->unsignedInteger('port');
            $table->string('source')->default('any');
            $table->string('status')->default('draft')->index();
            $table->text('description')->nullable();
            $table->timestamp('last_applied_at')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('source_path');
            $table->string('destination_path');
            $table->string('schedule')->default('daily');
            $table->unsignedInteger('retention_days')->default(14);
            $table->boolean('enabled')->default(true);
            $table->string('status')->default('ready')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_plan_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->string('archive_path')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('php_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('fpm_unit')->nullable();
            $table->string('fpm_socket')->nullable();
            $table->string('status')->default('available')->index();
            $table->json('extensions')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('docker_resources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 32)->default('container');
            $table->string('image')->nullable();
            $table->string('status')->default('unknown')->index();
            $table->json('ports')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('access_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->json('permissions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_levels');
        Schema::dropIfExists('docker_resources');
        Schema::dropIfExists('php_versions');
        Schema::dropIfExists('backup_runs');
        Schema::dropIfExists('backup_plans');
        Schema::dropIfExists('firewall_rules');
        Schema::dropIfExists('mail_accounts');
        Schema::dropIfExists('mail_domains');
        Schema::dropIfExists('ftp_accounts');
        Schema::dropIfExists('dns_records');
        Schema::dropIfExists('dns_zones');
        Schema::dropIfExists('system_services');
        Schema::dropIfExists('hosting_modules');
    }
};
