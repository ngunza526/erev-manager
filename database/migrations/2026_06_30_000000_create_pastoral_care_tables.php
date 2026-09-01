<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('visit_source')->default('culte');
            $table->date('visited_at');
            $table->string('follow_up_status')->default('a_relancer');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('new_converts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->date('conversion_date');
            $table->string('discipleship_stage')->default('accueil');
            $table->string('mentor_name')->nullable();
            $table->date('baptism_target_date')->nullable();
            $table->string('status')->default('en_suivi');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('full_name');
            $table->date('birth_date');
            $table->string('guardian_name');
            $table->string('guardian_phone')->nullable();
            $table->string('classroom')->nullable();
            $table->string('check_in_code')->nullable();
            $table->boolean('checked_in')->default(false);
            $table->timestamps();
        });

        Schema::create('volunteer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('volunteer_name');
            $table->string('team');
            $table->string('role');
            $table->date('service_date');
            $table->string('availability_status')->default('confirmed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('training_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->default('formation');
            $table->string('instructor_name');
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('enrollments_count')->default(0);
            $table->boolean('certificate_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('sermon_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('preacher')->nullable();
            $table->date('preached_at');
            $table->string('bible_reference')->nullable();
            $table->string('media_type')->default('audio');
            $table->string('public_url')->nullable();
            $table->boolean('is_public')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('security_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('incident_type')->default('general');
            $table->string('severity')->default('medium');
            $table->dateTime('occurred_at');
            $table->string('reported_by');
            $table->string('status')->default('open');
            $table->text('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_incidents');
        Schema::dropIfExists('sermon_media');
        Schema::dropIfExists('training_courses');
        Schema::dropIfExists('volunteer_assignments');
        Schema::dropIfExists('children');
        Schema::dropIfExists('new_converts');
        Schema::dropIfExists('visitors');
    }
};
