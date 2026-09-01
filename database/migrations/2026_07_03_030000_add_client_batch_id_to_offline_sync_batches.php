<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offline_sync_batches', function (Blueprint $table) {
            $table->string('client_batch_id')->after('device_id')->default('legacy');
            $table->unsignedInteger('processed_count')->default(0)->after('status');
            $table->unique(['church_id', 'device_id', 'client_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('offline_sync_batches', function (Blueprint $table) {
            $table->dropUnique(['church_id', 'device_id', 'client_batch_id']);
            $table->dropColumn(['client_batch_id', 'processed_count']);
        });
    }
};
