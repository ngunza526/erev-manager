<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solution_modules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category');
            $table->text('description');
            $table->string('church_central_reference');
            $table->text('rdc_adaptation');
            $table->string('status')->default('active');
            $table->boolean('is_core')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solution_modules');
    }
};
