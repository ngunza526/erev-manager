<?php

namespace App\Http\Controllers;

use App\Models\EventRegistration;
use App\Models\PublicContribution;
use App\Services\AccessScope;
use App\Services\Accounting\AccountingService;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * SEC-27 — Validation manuelle des contributions issues des formulaires publics.
 * Aucune ecriture comptable n'est passee avant qu'un agent porteur de
 * `contributions.record` n'ait valide la ligne ; le perimetre suit AccessScope.
 */
class PublicContributionController extends Controller
{
    public function index(Request $request, AccessScope $scope): Response
    {
        $base = fn () => $scope->scopeChurchOwned(PublicContribution::query(), $request->user());

        return Inertia::render('PublicContributions/Index', [
            'pending' => $base()
                ->where('status', PublicContribution::STATUS_PENDING)
                ->with('church:id,designation', 'event:id,title')
                ->latest()
                ->get(),
            'recent' => $base()
                ->whereIn('status', [PublicContribution::STATUS_VALIDATED, PublicContribution::STATUS_REJECTED])
                ->with('church:id,designation', 'event:id,title', 'reviewer:id,name')
                ->latest('reviewed_at')
                ->limit(30)
                ->get(),
        ]);
    }

    public function approve(Request $request, PublicContribution $publicContribution, AccessScope $scope, AccountingService $accounting): RedirectResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $publicContribution->church_id);
        abort_unless($publicContribution->status === PublicContribution::STATUS_PENDING, 422, 'Contribution deja traitee.');

        $entry = $publicContribution->kind === 'event_registration'
            ? $this->postEventEntry($publicContribution, $accounting)
            : $this->postDonationEntry($publicContribution, $accounting);

        $publicContribution->update([
            'status' => PublicContribution::STATUS_VALIDATED,
            'journal_entry_id' => $entry->id,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($publicContribution->event_registration_id) {
            EventRegistration::whereKey($publicContribution->event_registration_id)
                ->update(['journal_entry_id' => $entry->id]);
        }

        Audit::record('contribution.public.validated', $publicContribution, [
            'kind' => $publicContribution->kind,
            'amount' => (float) $publicContribution->amount,
            'currency' => $publicContribution->currency,
            'journal_entry_id' => $entry->id,
        ], (int) $publicContribution->church_id);

        return back()->with('success', 'Contribution validee et comptabilisee.');
    }

    public function reject(Request $request, PublicContribution $publicContribution, AccessScope $scope): RedirectResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $publicContribution->church_id);
        abort_unless($publicContribution->status === PublicContribution::STATUS_PENDING, 422, 'Contribution deja traitee.');

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $publicContribution->update([
            'status' => PublicContribution::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['note'] ?? null,
        ]);

        Audit::record('contribution.public.rejected', $publicContribution, [
            'kind' => $publicContribution->kind,
            'amount' => (float) $publicContribution->amount,
            'currency' => $publicContribution->currency,
        ], (int) $publicContribution->church_id);

        return back()->with('success', 'Contribution rejetee.');
    }

    private function postDonationEntry(PublicContribution $contribution, AccountingService $accounting)
    {
        return $accounting->recordCollection([
            'church_id' => $contribution->church_id,
            'type' => $contribution->contribution_type,
            'amount' => $contribution->amount,
            'currency' => $contribution->currency,
            'exchange_rate' => $contribution->exchange_rate,
            'cash_account_code' => $this->cashAccount($contribution->payment_method),
            'description' => 'Don public '.($contribution->contributor_name ?: 'anonyme'),
        ]);
    }

    private function postEventEntry(PublicContribution $contribution, AccountingService $accounting)
    {
        $title = $contribution->event?->title ?? 'evenement';

        return $accounting->recordBalancedEntry([
            'church_id' => $contribution->church_id,
            'type' => 'event_registration',
            'entry_date' => now()->toDateString(),
            'description' => "Inscription publique {$title}",
            'currency' => $contribution->currency,
            'exchange_rate' => $contribution->exchange_rate,
            'lines' => [
                ['account_code' => $this->cashAccount($contribution->payment_method), 'label' => 'Encaissement inscription publique', 'debit' => $contribution->amount, 'credit' => 0],
                ['account_code' => '704', 'label' => 'Revenu evenement', 'debit' => 0, 'credit' => $contribution->amount],
            ],
        ]);
    }

    private function cashAccount(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'bank', 'card' => '501',
            'mobile_money', 'mpesa', 'airtel_money', 'orange_money' => '515',
            default => '511',
        };
    }
}
