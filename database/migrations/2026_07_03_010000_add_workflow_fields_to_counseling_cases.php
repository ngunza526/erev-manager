<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('counseling_cases', function (Blueprint $table) {
            $table->date('next_follow_up_at')->nullable()->after('appointment_date');
            $table->dateTime('closed_at')->nullable()->after('status');
            $table->text('last_follow_up_note')->nullable()->after('summary');
        });
    }

    public function down(): void
    {
        Schema::table('counseling_cases', function (Blueprint $table) {
            $table->dropColumn(['next_follow_up_at', 'closed_at', 'last_follow_up_note']);
        });
    }
};
