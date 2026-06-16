<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trigger_type', 50)->default('manual'); // manual, webhook, pr, scheduled
            $table->string('source_type', 50)->default('repo'); // repo, pr, zip, requirement
            $table->string('source_ref', 500)->nullable(); // branch, pr-id, file path
            $table->string('status', 50)->default('pending');
            $table->smallInteger('overall_score')->nullable();
            $table->smallInteger('security_score')->nullable();
            $table->smallInteger('performance_score')->nullable();
            $table->smallInteger('architecture_score')->nullable();
            $table->smallInteger('maintainability_score')->nullable();
            $table->smallInteger('quality_score')->nullable();
            $table->smallInteger('debt_score')->nullable();
            $table->smallInteger('devops_score')->nullable();
            $table->string('ai_provider_used', 50)->nullable();
            $table->integer('tokens_consumed')->default(0);
            $table->integer('processing_ms')->nullable();
            $table->jsonb('agent_statuses')->nullable(); // per-agent status tracking
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['project_id', 'created_at']);
            $table->index('status');
        });

        Schema::create('issues', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('analysis_id')->constrained()->cascadeOnDelete();
            $table->string('agent_type', 50);
            $table->string('category', 100);
            $table->string('title', 500);
            $table->text('description');
            $table->string('severity', 20)->default('medium');
            $table->string('file_path', 1000)->nullable();
            $table->integer('line_start')->nullable();
            $table->integer('line_end')->nullable();
            $table->text('code_snippet')->nullable();
            $table->string('rule_id', 100)->nullable();
            $table->boolean('is_false_positive')->default(false);
            $table->timestamp('dismissed_at')->nullable();
            $table->foreignUuid('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['analysis_id', 'severity']);
            $table->index(['analysis_id', 'agent_type']);
        });

        Schema::create('recommendations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('analysis_id')->constrained()->cascadeOnDelete();
            $table->text('problem_description');
            $table->text('root_cause')->nullable();
            $table->string('risk_level', 20)->nullable();
            $table->text('recommended_fix');
            $table->text('code_before')->nullable();
            $table->text('code_after')->nullable();
            $table->string('estimated_improvement', 255)->nullable();
            $table->string('status', 50)->default('pending'); // pending, applied, dismissed
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['analysis_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
        Schema::dropIfExists('issues');
        Schema::dropIfExists('analyses');
    }
};
