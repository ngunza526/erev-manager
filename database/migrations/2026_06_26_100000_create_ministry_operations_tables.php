<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('service_type')->default('culte');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('preacher')->nullable();
            $table->string('worship_leader')->nullable();
            $table->unsignedInteger('attendance_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ministry_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('group_type')->default('cellule');
            $table->string('leader_name');
            $table->string('meeting_day')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->unsignedInteger('members_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('church_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('event_type')->default('conference');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('venue');
            $table->string('currency', 3)->default('CDF');
            $table->decimal('ticket_price', 18, 2)->default(0);
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('registrations_count')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('department')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('amount', 18, 2);
            $table->date('period_starts_at');
            $table->date('period_ends_at');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('vendor')->nullable();
            $table->string('category')->default('fonctionnement');
            $table->string('currency', 3)->default('USD');
            $table->decimal('amount', 18, 2);
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->date('expense_date');
            $table->string('payment_method')->default('cash');
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('church_events');
        Schema::dropIfExists('ministry_groups');
        Schema::dropIfExists('church_services');
    }
};
