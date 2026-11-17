<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->time('departs_time')->default('08:00:00')->after('returns_on');
            $table->time('returns_time')->default('16:00:00')->after('departs_time');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['departs_time', 'returns_time']);
        });
    }
};