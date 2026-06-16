<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_tests', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('analysis_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('issue_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50); // unit, feature, api, widget, integration
            $table->string('framework', 50)->default('phpunit'); // phpunit, pest, flutter_test
            $table->string('class_name', 255);
            $table->text('test_code');
            $table->string('scenario', 500)->nullable();
            $table->string('coverage_area', 255)->nullable();
            $table->string('validation_status', 50)->default('pending'); // pending, passed, failed
            $table->timestamp('created_at')->useCurrent();

            $table->index(['analysis_id', 'type']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('analysis_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('format', 20)->default('pdf'); // pdf, json, html
            $table->string('storage_path', 1000)->nullable();
            $table->jsonb('summary')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['organization_id', 'created_at']);
            $table->index('analysis_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('generated_tests');
    }
};
