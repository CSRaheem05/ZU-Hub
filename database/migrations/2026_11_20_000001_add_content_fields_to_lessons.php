<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Course content: an optional description for every lesson, and uploaded materials (PDF / documents).
 *
 * Purely ADDITIVE and safe to run on live data:
 *   - five new NULLABLE columns (existing rows simply get NULL);
 *   - the lesson_type ENUM gains one value, 'file' (video / link / live keep working untouched);
 *   - nothing is dropped, renamed or rewritten, and no row is modified.
 *
 * Run it on its own (this database was created from a SQL dump):
 *   php artisan migrate --path=database/migrations/2026_11_20_000001_add_content_fields_to_lessons.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('lessons', 'description')) {
                $table->text('description')->nullable()->after('lesson_title');
            }
            if (! Schema::hasColumn('lessons', 'file_path')) {
                $table->string('file_path', 500)->nullable()->after('scheduled_at');   // private disk, e.g. lesson-files/<uuid>.pdf
            }
            if (! Schema::hasColumn('lessons', 'file_name')) {
                $table->string('file_name', 255)->nullable()->after('file_path');      // original name, for display and download
            }
            if (! Schema::hasColumn('lessons', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('file_name');   // bytes
            }
            if (! Schema::hasColumn('lessons', 'file_mime')) {
                $table->string('file_mime', 120)->nullable()->after('file_size');      // detected from the file content
            }
        });

        // Adding a value to an ENUM keeps every existing row as it is.
        DB::statement("ALTER TABLE `lessons` MODIFY `lesson_type` ENUM('video','link','live','file') NOT NULL");
    }

    public function down(): void
    {
        // Never throw away uploaded materials by accident.
        if (DB::table('lessons')->where('lesson_type', 'file')->exists()) {
            throw new RuntimeException('Cannot roll back: some lessons are uploaded materials (lesson_type = file). Remove them first.');
        }

        DB::statement("ALTER TABLE `lessons` MODIFY `lesson_type` ENUM('video','link','live') NOT NULL");

        Schema::table('lessons', function (Blueprint $table) {
            foreach (['file_mime', 'file_size', 'file_name', 'file_path', 'description'] as $column) {
                if (Schema::hasColumn('lessons', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
