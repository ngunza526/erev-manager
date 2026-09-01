<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('channel')->default('sms');
            $table->string('audience')->default('membres');
            $table->string('subject');
            $table->text('body');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('requester_name');
            $table->string('request_type');
            $table->string('priority')->default('normal');
            $table->string('assigned_to')->nullable();
            $table->date('due_at')->nullable();
            $table->string('status')->default('open');
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('facility_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('requester_name');
            $table->string('facility_name');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('fee_currency', 3)->default('CDF');
            $table->decimal('fee_amount', 15, 2)->default(0);
            $table->string('payment_status')->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('church_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('location')->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('value_currency', 3)->default('USD');
            $table->decimal('value_amount', 15, 2)->default(0);
            $table->string('condition_status')->default('bon');
            $table->string('custodian')->nullable();
            $table->timestamps();
        });

        Schema::create('board_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('meeting_date');
            $table->string('chairperson');
            $table->unsignedInteger('quorum_count')->default(0);
            $table->text('decisions');
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('pledges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('donor_name');
            $table->string('campaign');
            $table->string('currency', 3)->default('USD');
            $table->decimal('pledged_amount', 15, 2);
            $table->decimal('received_amount', 15, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('audience')->default('membres');
            $table->date('opens_at');
            $table->date('closes_at')->nullable();
            $table->unsignedInteger('responses_count')->default(0);
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('testimonies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('author_name');
            $table->date('testimony_date');
            $table->string('category')->default('general');
            $table->string('moderation_status')->default('pending');
            $table->boolean('is_public')->default(false);
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonies');
        Schema::dropIfExists('surveys');
        Schema::dropIfExists('pledges');
        Schema::dropIfExists('board_meetings');
        Schema::dropIfExists('church_assets');
        Schema::dropIfExists('facility_bookings');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('communications');
    }
};
