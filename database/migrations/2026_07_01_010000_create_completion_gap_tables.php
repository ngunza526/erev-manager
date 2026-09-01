<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('household_name');
            $table->string('primary_contact_name');
            $table->string('phone')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->default('Lubumbashi');
            $table->unsignedInteger('members_count')->default(1);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('discipleship_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('participant_name');
            $table->string('track_name');
            $table->string('current_step')->default('accueil');
            $table->unsignedInteger('progress_percent')->default(0);
            $table->string('mentor_name')->nullable();
            $table->date('next_meeting_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('church_media_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('media_type')->default('image');
            $table->string('category')->default('culte');
            $table->string('storage_url')->nullable();
            $table->string('copyright_status')->default('interne');
            $table->boolean('offline_available')->default(false);
            $table->string('status')->default('published');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('restriction_type')->default('affecte');
            $table->string('currency', 3)->default('USD');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fund_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('movement_type')->default('receipt');
            $table->string('source_name')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('amount', 15, 2);
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->date('movement_date');
            $table->string('payment_method')->default('cash');
            $table->string('status')->default('draft');
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('church_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('attendee_name');
            $table->string('phone')->nullable();
            $table->string('ticket_code')->unique();
            $table->string('currency', 3)->default('CDF');
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->string('payment_method')->default('cash');
            $table->string('check_in_status')->default('registered');
            $table->dateTime('checked_in_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('fund_movements');
        Schema::dropIfExists('funds');
        Schema::dropIfExists('church_media_items');
        Schema::dropIfExists('discipleship_paths');
        Schema::dropIfExists('families');
    }
};
