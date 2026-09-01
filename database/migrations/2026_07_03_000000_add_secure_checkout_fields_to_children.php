<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->dateTime('checked_in_at')->nullable()->after('checked_in');
            $table->dateTime('checked_out_at')->nullable()->after('checked_in_at');
            $table->string('released_to')->nullable()->after('checked_out_at');
        });
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'checked_out_at', 'released_to']);
        });
    }
};
