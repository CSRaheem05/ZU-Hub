<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Announcements + notification inbox.
 *
 *  - `announcements`  : what an admin/instructor writes (draft or published).
 *  - `notifications`  : the EXISTING per-user inbox table is reused. It gains an
 *                       `announcement_id` link and `read_at`, and `offering_id`
 *                       becomes nullable (a university-wide announcement has no course).
 *
 * Additive only: no existing column or row is dropped or rewritten.
 * Written to match the existing ZU_Hub SQL schema (string keys, utf8mb4_general_ci).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';

                $table->string('announcement_id', 50)->primary();
                $table->string('author_id', 50);
                $table->enum('author_role', ['manager', 'instructor']);
                // all = every user, students / instructors = by role,
                // course = students enrolled in one offering, my_students = every student of the author's offerings
                $table->enum('audience', ['all', 'students', 'instructors', 'course', 'my_students']);
                $table->string('offering_id', 50)->nullable();
                $table->string('title', 160);
                $table->text('body');
                $table->enum('priority', ['normal', 'important', 'urgent'])->default('normal');
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable();

                $table->index(['status', 'published_at'], 'idx_announcements_status');
                $table->index('author_id', 'idx_announcements_author');
                $table->index('offering_id', 'idx_announcements_offering');
                $table->foreign('author_id', 'fk_announcements_author')->references('user_id')->on('users')->cascadeOnDelete();
                $table->foreign('offering_id', 'fk_announcements_offering')->references('offering_id')->on('course_offerings')->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('notifications', 'announcement_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->string('announcement_id', 50)->nullable()->after('offering_id');
                $table->timestamp('read_at')->nullable()->after('is_read');
            });

            // offering_id used to be NOT NULL; announcements are not always tied to a course.
            DB::statement('ALTER TABLE `notifications` MODIFY `offering_id` VARCHAR(50) NULL');

            Schema::table('notifications', function (Blueprint $table) {
                $table->foreign('announcement_id', 'fk_notifications_announcement')->references('announcement_id')->on('announcements')->cascadeOnDelete();
                $table->unique(['user_id', 'announcement_id'], 'uq_notifications_user_announcement');
                $table->index(['user_id', 'is_read', 'created_at'], 'idx_notifications_inbox');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifications', 'announcement_id')) {
            // Only rows created by this feature (linked to an announcement) are removed.
            DB::table('notifications')->whereNotNull('announcement_id')->delete();

            Schema::table('notifications', function (Blueprint $table) {
                $table->dropForeign('fk_notifications_announcement');
                $table->dropUnique('uq_notifications_user_announcement');
                $table->dropIndex('idx_notifications_inbox');
                $table->dropColumn(['announcement_id', 'read_at']);
            });

            DB::table('notifications')->whereNull('offering_id')->delete();
            DB::statement('ALTER TABLE `notifications` MODIFY `offering_id` VARCHAR(50) NOT NULL');
        }

        Schema::dropIfExists('announcements');
    }
};
