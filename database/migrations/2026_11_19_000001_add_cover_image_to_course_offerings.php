<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Course cover images.
 *
 * `cover_image` holds the path of the uploaded image on the "public" disk
 * (e.g. "course-covers/ab12….jpg"). NULL = the course has no upload and the
 * UI shows a generated fallback cover. Additive and nullable: existing rows
 * are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('course_offerings', 'cover_image')) {
            Schema::table('course_offerings', function (Blueprint $table) {
                $table->string('cover_image', 255)->nullable()->after('topic_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_offerings', 'cover_image')) {
            Schema::table('course_offerings', fn (Blueprint $table) => $table->dropColumn('cover_image'));
        }
    }
};
