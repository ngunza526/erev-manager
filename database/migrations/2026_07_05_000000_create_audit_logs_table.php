<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Identite denormalisee : survit a la suppression du compte.
            $table->string('actor_label')->nullable();
            $table->string('action')->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            // Perimetre : church_id pour les evenements eglise, community_id pour
            // les evenements de niveau coordination (creation d'un compte
            // coordination, changements RBAC portes par une communaute...).
            $table->foreignId('church_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('community_id')->nullable()->constrained()->nullOnDelete();
            $table->json('context')->nullable();
            $table->string('ip_address', 45)->nullable();
            // Journal append-only : horodatage de creation uniquement.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['church_id', 'created_at']);
            $table->index(['community_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
