<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('designation');
            $table->string('headquarters_number')->nullable();
            $table->string('headquarters_avenue')->nullable();
            $table->string('headquarters_district')->nullable();
            $table->string('headquarters_city');
            $table->string('headquarters_province');
            $table->string('headquarters_country')->default('RDC');
            $table->string('authorization_number')->unique();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('churches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->string('designation');
            $table->string('address_number')->nullable();
            $table->string('address_avenue')->nullable();
            $table->string('address_district');
            $table->string('address_city');
            $table->string('address_province');
            $table->string('address_country')->default('RDC');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('last_name');
            $table->string('middle_name');
            $table->string('first_name');
            $table->string('sex');
            $table->date('birth_date');
            $table->string('birth_place');
            $table->string('profession');
            $table->string('marital_status');
            $table->string('spouse')->nullable();
            $table->date('baptism_date')->nullable();
            $table->string('baptism_place')->nullable();
            $table->string('baptism_church')->nullable();
            $table->string('identity_type')->nullable();
            $table->string('identity_number')->nullable();
            $table->date('identity_issued_at')->nullable();
            $table->string('identity_issuer')->nullable();
            $table->string('status')->default('sympathisant');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('church_id')->nullable()->after('member_id')->constrained()->nullOnDelete();
            $table->foreignId('community_id')->nullable()->after('church_id')->constrained()->nullOnDelete();
            $table->string('level')->default('eglise')->after('password');
            $table->string('status')->default('actif')->after('level');
            $table->string('otp_secret')->nullable()->after('remember_token');
            $table->timestamp('otp_verified_at')->nullable()->after('otp_secret');
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->boolean('is_base')->default(false);
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 18, 6);
            $table->date('rated_at');
            $table->string('source')->default('manuel');
            $table->timestamps();
            $table->unique(['from_currency', 'to_currency', 'rated_at']);
        });

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->unsignedTinyInteger('class');
            $table->string('normal_side');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->date('entry_date');
            $table->string('type');
            $table->string('description');
            $table->string('currency', 3);
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->string('status')->default('brouillon');
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained()->restrictOnDelete();
            $table->string('label');
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('provider')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('offline_sync_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_id');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->timestamp('synced_at')->nullable();
            $table->json('conflicts')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sync_batches');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_id');
            $table->dropConstrainedForeignId('church_id');
            $table->dropConstrainedForeignId('community_id');
            $table->dropColumn(['level', 'status', 'otp_secret', 'otp_verified_at']);
        });

        Schema::dropIfExists('members');
        Schema::dropIfExists('churches');
        Schema::dropIfExists('communities');
    }
};
