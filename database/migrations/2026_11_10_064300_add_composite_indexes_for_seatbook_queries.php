<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            $table->index(['event_id', 'status'], 'seats_event_id_status_index');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->index(['status', 'starts_at'], 'events_status_starts_at_index');
        });

        Schema::table('seat_holds', function (Blueprint $table) {
            $table->index(['user_id', 'expires_at'], 'seat_holds_user_id_expires_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            $table->dropIndex('seats_event_id_status_index');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_status_starts_at_index');
        });

        Schema::table('seat_holds', function (Blueprint $table) {
            $table->dropIndex('seat_holds_user_id_expires_at_index');
        });
    }
};
