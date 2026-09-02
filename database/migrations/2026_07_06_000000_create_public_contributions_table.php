<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEC-27 — File d'attente des contributions issues des formulaires publics
 * (dons, inscriptions payantes). Rien n'est ecrit au grand livre tant qu'un
 * agent porteur de `contributions.record` n'a pas valide la ligne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('kind'); // donation | event_registration
            $table->foreignId('church_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contributor_name')->nullable();
            $table->string('phone', 80)->nullable();
            $table->string('contribution_type')->nullable(); // dime | offrande | don
            $table->string('currency', 3);
            $table->decimal('amount', 15, 2);
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->string('payment_method');
            $table->string('status')->default('pending')->index(); // pending | validated | rejected
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note')->nullable();
            $table->timestamps();

            $table->index(['church_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_contributions');
    }
};
