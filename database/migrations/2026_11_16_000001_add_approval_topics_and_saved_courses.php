<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function hasForeignKey(string $table, string $column): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }

    public function up(): void
    {
        // This migration can resume after MariaDB stops at the topics FK.
        // New columns may already exist because DDL isn't automatically rolled back.
        $newApproval = !Schema::hasColumn('course_offerings', 'approval_status');
        Schema::table('course_offerings', function (Blueprint $table) {
            if (!Schema::hasColumn('course_offerings', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'changes_requested', 'approved', 'rejected'])->default('pending');
            }
            if (!Schema::hasColumn('course_offerings', 'review_note')) $table->text('review_note')->nullable();
            if (!Schema::hasColumn('course_offerings', 'reviewed_by')) $table->string('reviewed_by', 50)->nullable();
            if (!Schema::hasColumn('course_offerings', 'reviewed_at')) $table->timestamp('reviewed_at')->nullable();
            if (!Schema::hasColumn('course_offerings', 'course_status')) $table->enum('course_status', ['ongoing', 'completed'])->default('ongoing');
            if (!Schema::hasColumn('course_offerings', 'completed_at')) $table->timestamp('completed_at')->nullable();
            if (!Schema::hasColumn('course_offerings', 'topic_id')) $table->string('topic_id', 50)->nullable();
        });

        // Grandfather only the old courses on first creation; do not reset later reviews.
        if ($newApproval) {
            DB::table('course_offerings')->update(['approval_status' => 'approved']);
        }

        if (!Schema::hasTable('topics')) {
            Schema::create('topics', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci'; // Must match imported colleges table
                $table->string('topic_id', 50)->primary();
                $table->string('college_code', 20);
                $table->string('topic_name', 120);
                $table->unique(['college_code', 'topic_name']);
            });
        }
        // Fix the topics table left behind by the previous failed migration.
        DB::statement('ALTER TABLE `topics` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');

        if (!$this->hasForeignKey('topics', 'college_code')) {
            Schema::table('topics', function (Blueprint $table) {
                $table->foreign('college_code')->references('college_code')->on('colleges');
            });
        }

        foreach (DB::table('colleges')->pluck('college_code') as $code) {
            DB::table('topics')->updateOrInsert(
                ['topic_id' => 'general-' . $code],
                ['college_code' => $code, 'topic_name' => 'General']
            );
        }
        if (DB::table('colleges')->where('college_code', '15')->exists()) {
            foreach (['PHP', 'Python', 'Java', 'Databases', 'Web Development', 'Cybersecurity', 'Artificial Intelligence'] as $topic) {
                DB::table('topics')->updateOrInsert(
                    ['topic_id' => 'it-' . strtolower(str_replace(' ', '-', $topic))],
                    ['college_code' => '15', 'topic_name' => $topic]
                );
            }
        }

        if (!$this->hasForeignKey('course_offerings', 'topic_id')) {
            Schema::table('course_offerings', function (Blueprint $table) {
                $table->foreign('topic_id')->references('topic_id')->on('topics')->nullOnDelete();
            });
        }
        if (!$this->hasForeignKey('course_offerings', 'reviewed_by')) {
            Schema::table('course_offerings', function (Blueprint $table) {
                $table->foreign('reviewed_by')->references('user_id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('user_saved_courses')) {
            Schema::create('user_saved_courses', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';
                $table->string('user_id', 50);
                $table->string('offering_id', 50);
                $table->timestamp('saved_at')->useCurrent();
                $table->primary(['user_id', 'offering_id']);
                $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
                $table->foreign('offering_id')->references('offering_id')->on('course_offerings')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_saved_courses');
        if (Schema::hasTable('course_offerings')) {
            if ($this->hasForeignKey('course_offerings', 'reviewed_by')) {
                Schema::table('course_offerings', fn (Blueprint $table) => $table->dropForeign(['reviewed_by']));
            }
            if ($this->hasForeignKey('course_offerings', 'topic_id')) {
                Schema::table('course_offerings', fn (Blueprint $table) => $table->dropForeign(['topic_id']));
            }
            $columns = array_filter(
                ['approval_status', 'review_note', 'reviewed_by', 'reviewed_at', 'course_status', 'completed_at', 'topic_id'],
                fn ($column) => Schema::hasColumn('course_offerings', $column)
            );
            if ($columns) Schema::table('course_offerings', fn (Blueprint $table) => $table->dropColumn($columns));
        }
        Schema::dropIfExists('topics');
    }
};
