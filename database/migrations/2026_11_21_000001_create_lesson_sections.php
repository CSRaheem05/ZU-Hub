<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Course sections: lessons are grouped into named, ordered sections ("Getting Started", "Week 1", ...).
 *
 * ADDITIVE and safe on live data:
 *   1. a NEW table  lesson_sections  (section_id, offering_id, title, section_order, timestamps);
 *   2. a NEW nullable column  lessons.section_id  (foreign key, ON DELETE SET NULL);
 *   3. every course that already has lessons gets ONE section, "Getting Started", and its lessons are
 *      attached to it. lesson_id, lesson_order, progress and every file record are left exactly as they are,
 *      so student progress and the existing order are preserved. Nothing is dropped, renamed or overwritten.
 *
 * Run it on its own (this database was created from a SQL dump):
 *   php artisan migrate --path=database/migrations/2026_11_21_000001_create_lesson_sections.php
 */
return new class extends Migration
{
    private const DEFAULT_TITLE = 'Getting Started';

    public function up(): void
    {
        if (! Schema::hasTable('lesson_sections')) {
            Schema::create('lesson_sections', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';   // same as the existing tables, so the foreign keys match

                $table->string('section_id', 50)->primary();
                $table->string('offering_id', 50);
                $table->string('title', 255);
                $table->unsignedInteger('section_order')->default(0);
                $table->timestamps();

                $table->index(['offering_id', 'section_order'], 'idx_sections_offering_order');
                $table->foreign('offering_id', 'fk_sections_offering')
                    ->references('offering_id')->on('course_offerings')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('lessons', 'section_id')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->string('section_id', 50)->nullable()->after('offering_id');
                $table->index('section_id', 'idx_lessons_section');
                $table->foreign('section_id', 'fk_lessons_section')
                    ->references('section_id')->on('lesson_sections')
                    ->nullOnDelete();   // deleting a section can never delete a lesson
            });
        }

        // Existing lessons move into one default section per course (order and ids untouched).
        DB::transaction(function () {
            $offerings = DB::table('lessons')->whereNull('section_id')->distinct()->pluck('offering_id');

            foreach ($offerings as $offeringId) {
                $sectionId = DB::table('lesson_sections')->where('offering_id', $offeringId)->orderBy('section_order')->value('section_id');

                if (! $sectionId) {
                    $sectionId = (string) Str::uuid();
                    DB::table('lesson_sections')->insert([
                        'section_id' => $sectionId,
                        'offering_id' => $offeringId,
                        'title' => self::DEFAULT_TITLE,
                        'section_order' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('lessons')->where('offering_id', $offeringId)->whereNull('section_id')->update(['section_id' => $sectionId]);
            }
        });
    }

    public function down(): void
    {
        // Lessons are never touched: only the grouping is removed.
        if (Schema::hasColumn('lessons', 'section_id')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->dropForeign('fk_lessons_section');
                $table->dropIndex('idx_lessons_section');
                $table->dropColumn('section_id');
            });
        }

        Schema::dropIfExists('lesson_sections');
    }
};
