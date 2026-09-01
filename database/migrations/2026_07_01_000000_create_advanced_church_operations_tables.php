<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name');
            $table->string('buyer_name')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('currency', 3)->default('CDF');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->string('payment_method')->default('cash');
            $table->string('status')->default('draft');
            $table->date('sold_at');
            $table->timestamps();
        });

        Schema::create('vendor_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vendor_name');
            $table->string('bill_number')->nullable();
            $table->string('category')->default('fonctionnement');
            $table->string('currency', 3)->default('USD');
            $table->decimal('amount', 15, 2);
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->string('payment_method')->default('bank');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period_label');
            $table->string('staff_name');
            $table->string('role');
            $table->string('currency', 3)->default('USD');
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('social_charges', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->string('payment_method')->default('bank');
            $table->string('status')->default('draft');
            $table->date('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('account_name');
            $table->string('currency', 3)->default('USD');
            $table->date('statement_date');
            $table->decimal('book_balance', 15, 2);
            $table->decimal('statement_balance', 15, 2);
            $table->decimal('difference_amount', 15, 2)->default(0);
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('church_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('beneficiary');
            $table->string('purpose');
            $table->string('currency', 3)->default('USD');
            $table->decimal('amount', 15, 2);
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->date('payout_date');
            $table->string('payment_method')->default('bank');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('counseling_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('case_code')->unique();
            $table->string('requester_name');
            $table->string('care_type')->default('pastoral');
            $table->string('assigned_to')->nullable();
            $table->date('appointment_date')->nullable();
            $table->string('confidentiality_level')->default('restreint');
            $table->string('status')->default('open');
            $table->text('summary');
            $table->timestamps();
        });

        Schema::create('outreach_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('location');
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('volunteers_count')->default(0);
            $table->unsignedInteger('contacts_count')->default(0);
            $table->unsignedInteger('conversions_count')->default(0);
            $table->string('status')->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('public_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('target_type')->default('don');
            $table->string('target_url');
            $table->string('short_code')->unique();
            $table->unsignedInteger('scan_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('live_stream_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->dateTime('starts_at');
            $table->string('platform')->default('facebook');
            $table->string('stream_url')->nullable();
            $table->string('fallback_mode')->default('audio');
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_tool_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('tool_type')->default('redaction');
            $table->string('requested_by');
            $table->string('prompt_title');
            $table->text('prompt_context');
            $table->string('human_review_status')->default('pending');
            $table->text('output_summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tool_requests');
        Schema::dropIfExists('live_stream_sessions');
        Schema::dropIfExists('public_qr_codes');
        Schema::dropIfExists('outreach_campaigns');
        Schema::dropIfExists('counseling_cases');
        Schema::dropIfExists('church_payouts');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('vendor_bills');
        Schema::dropIfExists('resource_sales');
    }
};
