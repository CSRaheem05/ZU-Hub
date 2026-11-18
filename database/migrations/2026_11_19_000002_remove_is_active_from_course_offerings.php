<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permanently remove Active / Inactive from courses (university supervisor's decision).
 *
 * A course now has exactly two independent states:
 *   approval_status : pending | changes_requested | approved | rejected
 *   course_status   : ongoing | completed
 * and "is_active" no longer exists.
 *
 * SAFETY
 *  - Only the `is_active` column is dropped. No course row, lesson, enrolment,
 *    saved course or notification is touched.
 *  - Before dropping, every course's old value is copied into
 *    `course_offerings_is_active_backup`, so down() can restore the column EXACTLY
 *    as it was. (That small table can be dropped manually once you are certain
 *    you will never roll back.)
 *  - `is_active` is not part of any index, foreign key, trigger, view or routine
 *    (verified against the live schema), so nothing else depends on it.
 *  - Guarded with hasColumn()/hasTable(): running it twice does nothing.
 *
 * NOTE for review: courses that are Approved but currently have is_active = 0
 * (deactivated by their instructor) become visible to students once the column is
 * gone, because "approved" is now the only visibility rule.
 *
 * Run it ON ITS OWN (the stock Laravel migrations in this project show as "pending"
 * because the database was created from a SQL dump):
 *   php artisan migrate --path=database/migrations/2026_11_19_000002_remove_is_active_from_course_offerings.php
 */
return new class extends Migration
{
    private const BACKUP = 'course_offerings_is_active_backup';

    public function up(): void
    {
        if (! Schema::hasColumn('course_offerings', 'is_active')) {
            return;
        }

        // 1) keep a faithful copy of the old values
        if (! Schema::hasTable(self::BACKUP)) {
            Schema::create(self::BACKUP, function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';

                $table->string('offering_id', 50)->primary();
                $table->boolean('is_active');
                $table->timestamp('backed_up_at')->useCurrent();
            });
        }

        DB::statement(
            'INSERT IGNORE INTO `'.self::BACKUP.'` (`offering_id`, `is_active`) '
            .'SELECT `offering_id`, `is_active` FROM `course_offerings`'
        );

        // 2) drop only that column
        Schema::table('course_offerings', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_offerings', 'is_active')) {
            return;
        }

        Schema::table('course_offerings', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('lecture_schedule');
        });

        if (Schema::hasTable(self::BACKUP)) {
            // restore every course's exact previous value
            DB::statement(
                'UPDATE `course_offerings` o JOIN `'.self::BACKUP.'` b ON b.`offering_id` = o.`offering_id` '
                .'SET o.`is_active` = b.`is_active`'
            );
            Schema::drop(self::BACKUP);
        }
    }
};
