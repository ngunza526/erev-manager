<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revue du modele finance (2026-09-05) : "reservations-locaux" et
 * "promesses-dons" font deja apparaitre un montant d'argent (fee_amount /
 * received_amount) mais ne posaient jamais d'ecriture comptable. On les
 * aligne sur le reste de l'application (Expense, VendorBill, PayrollRun...)
 * en leur ajoutant un rattachement a une ecriture et un moyen de paiement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_bookings', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('fee_amount');
            $table->foreignId('journal_entry_id')->nullable()->after('payment_status')->constrained()->nullOnDelete();
        });

        Schema::table('pledges', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('received_amount');
            $table->foreignId('journal_entry_id')->nullable()->after('status')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('facility_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
            $table->dropColumn('payment_method');
        });

        Schema::table('pledges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
            $table->dropColumn('payment_method');
        });
    }
};
