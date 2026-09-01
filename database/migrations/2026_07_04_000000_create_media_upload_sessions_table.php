<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_upload_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('upload_id')->unique();
            $table->string('title');
            $table->string('media_type')->default('file');
            $table->string('category')->default('mediatheque');
            $table->string('original_filename');
            $table->unsignedInteger('total_chunks');
            $table->json('received_chunks')->nullable();
            $table->string('status')->default('initiated');
            $table->string('storage_path')->nullable();
            $table->string('storage_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_upload_sessions');
    }
};
