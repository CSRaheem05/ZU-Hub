<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Scheduling window + one-time banner tracking.
 *
 *  announcements.publish_at  when the announcement becomes visible to recipients
 *  announcements.expires_at  when it stops being an active alert (null = never)
 *  notifications.alert_seen_at  when the slide-down banner was first shown to that user
 *                               (so the banner appears once per announcement per user)
 *
 * Additive only. Existing published announcements keep working: their publish_at is
 * back-filled from the existing published_at (a NEW column is written, nothing is overwritten).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('announcements', 'publish_at')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->timestamp('publish_at')->nullable()->after('status');
                $table->timestamp('expires_at')->nullable()->after('publish_at');
                $table->index(['status', 'publish_at', 'expires_at'], 'idx_announcements_window');
            });

            DB::table('announcements')
                ->where('status', 'published')
                ->whereNull('publish_at')
                ->update(['publish_at' => DB::raw('published_at')]);
        }

        if (! Schema::hasColumn('notifications', 'alert_seen_at')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->timestamp('alert_seen_at')->nullable()->after('read_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifications', 'alert_seen_at')) {
            Schema::table('notifications', fn (Blueprint $table) => $table->dropColumn('alert_seen_at'));
        }

        if (Schema::hasColumn('announcements', 'publish_at')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropIndex('idx_announcements_window');
                $table->dropColumn(['publish_at', 'expires_at']);
            });
        }
    }
};
