<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20); // bitbucket, github, gitlab
            $table->string('provider_repo_id', 255);
            $table->string('full_name', 500); // org/repo-name
            $table->text('clone_url');
            $table->string('default_branch', 255)->default('main');
            $table->boolean('is_private')->default(true);
            $table->string('webhook_id')->nullable();
            $table->text('webhook_secret')->nullable(); // encrypted
            $table->text('access_token')->nullable(); // encrypted
            $table->text('refresh_token')->nullable(); // encrypted
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'provider']);
            $table->unique(['organization_id', 'provider', 'provider_repo_id']);
        });

        Schema::create('pull_requests', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('repository_id')->constrained()->cascadeOnDelete();
            $table->string('provider_pr_id', 255);
            $table->string('title', 500);
            $table->string('source_branch', 255);
            $table->string('target_branch', 255);
            $table->string('author', 255)->nullable();
            $table->string('status', 50)->default('open'); // open, merged, closed
            $table->text('diff_url')->nullable();
            $table->timestamps();

            $table->index(['repository_id', 'status']);
            $table->unique(['repository_id', 'provider_pr_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pull_requests');
        Schema::dropIfExists('repositories');
    }
};
