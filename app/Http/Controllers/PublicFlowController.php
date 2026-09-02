<?php

namespace App\Http\Controllers;

use App\Models\Church;
use App\Models\ChurchEvent;
use App\Models\EventRegistration;
use App\Models\ExchangeRate;
use App\Models\PublicContribution;
use App\Models\Visitor;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicFlowController extends Controller
{
    public function donation(Church $church): Response
    {
        return Inertia::render('Public/Donation', [
            'church' => $church->only('id', 'designation', 'address_city', 'address_province'),
        ]);
    }

    public function storeDonation(Request $request, Church $church): RedirectResponse
    {
        $data = $request->validate([
            'giver_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['dime', 'offrande', 'don'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'payment_method' => ['required', Rule::in(['cash', 'bank', 'card', 'mobile_money', 'mpesa', 'airtel_money', 'orange_money'])],
            'phone' => ['nullable', 'string', 'max:80'],
        ]);

        // SEC-27 : montant plafonne, taux resolu cote serveur, et surtout aucune
        // ecriture comptable ici — la contribution attend la validation d'un agent.
        $this->guardPublicAmount($data['amount'], $data['currency'], 'amount');

        $contribution = PublicContribution::create([
            'church_id' => $church->id,
            'kind' => 'donation',
            'contributor_name' => $data['giver_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'contribution_type' => $data['type'],
            'currency' => $data['currency'],
            'amount' => $data['amount'],
            'exchange_rate' => $this->serverExchangeRate($data['currency']),
            'payment_method' => $data['payment_method'],
            'status' => PublicContribution::STATUS_PENDING,
        ]);

        Audit::record('contribution.public.submitted', $contribution, [
            'kind' => 'donation',
            'amount' => (float) $data['amount'],
            'currency' => $data['currency'],
        ], $church->id);

        return back()->with('success', "Contribution recue, en attente de validation par l'eglise.");
    }

    public function visitor(Church $church): Response
    {
        return Inertia::render('Public/Visitor', [
            'church' => $church->only('id', 'designation', 'address_city', 'address_province'),
        ]);
    }

    public function storeVisitor(Request $request, Church $church): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'visit_source' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        Visitor::create([
            ...$data,
            'church_id' => $church->id,
            'visited_at' => now()->toDateString(),
            'follow_up_status' => 'a_relancer',
        ]);

        return back()->with('success', 'Bienvenue, votre visite est enregistree.');
    }

    public function event(ChurchEvent $event): Response
    {
        return Inertia::render('Public/EventRegistration', [
            'event' => [
                'id' => $event->id,
                'church_id' => $event->church_id,
                'title' => $event->title,
                'venue' => $event->venue,
                'currency' => $event->currency,
                'ticket_price' => $event->ticket_price,
                'starts_at' => $event->starts_at,
            ],
            'church' => $event->church?->only('id', 'designation'),
        ]);
    }

    public function storeEvent(Request $request, ChurchEvent $event): RedirectResponse
    {
        $data = $request->validate([
            'attendee_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'payment_method' => ['required', Rule::in(['cash', 'bank', 'card', 'mobile_money', 'mpesa', 'airtel_money', 'orange_money'])],
        ]);

        $amountPaid = (float) ($data['amount_paid'] ?? 0);
        if ($amountPaid > 0) {
            $this->guardPublicAmount($amountPaid, $data['currency'], 'amount_paid');
        }

        $exchangeRate = $this->serverExchangeRate($data['currency']);

        // Le billet est emis tout de suite ; la comptabilisation de l'encaissement
        // attend la validation d'un agent (SEC-27) -> journal_entry_id reste nul.
        $registration = EventRegistration::create([
            'church_id' => $event->church_id,
            'church_event_id' => $event->id,
            'journal_entry_id' => null,
            'attendee_name' => $data['attendee_name'],
            'phone' => $data['phone'] ?? null,
            'ticket_code' => 'PUB-'.strtoupper(Str::random(8)),
            'currency' => $data['currency'],
            'amount_paid' => $amountPaid,
            'exchange_rate' => $exchangeRate,
            'payment_method' => $data['payment_method'],
            'check_in_status' => 'registered',
        ]);

        $event->increment('registrations_count');

        if ($amountPaid > 0) {
            $contribution = PublicContribution::create([
                'church_id' => $event->church_id,
                'kind' => 'event_registration',
                'church_event_id' => $event->id,
                'event_registration_id' => $registration->id,
                'contributor_name' => $data['attendee_name'],
                'phone' => $data['phone'] ?? null,
                'currency' => $data['currency'],
                'amount' => $amountPaid,
                'exchange_rate' => $exchangeRate,
                'payment_method' => $data['payment_method'],
                'status' => PublicContribution::STATUS_PENDING,
            ]);

            Audit::record('contribution.public.submitted', $contribution, [
                'kind' => 'event_registration',
                'event' => $event->title,
                'amount' => $amountPaid,
                'currency' => $data['currency'],
            ], $event->church_id);
        }

        return back()->with('success', 'Inscription confirmee.');
    }

    /**
     * SEC-27 : taux de change de reference (USD -> devise) resolu cote serveur.
     * USD est la devise de base ; toute autre devise sans taux connu retombe a 1.
     */
    private function serverExchangeRate(string $currency): float
    {
        if ($currency === 'USD') {
            return 1.0;
        }

        $rate = ExchangeRate::where('from_currency', 'USD')
            ->where('to_currency', $currency)
            ->latest('rated_at')
            ->value('rate');

        return (float) ($rate ?: 1.0);
    }

    /**
     * SEC-27 : plafond par devise sur les encaissements publics non authentifies.
     */
    private function guardPublicAmount(float $amount, string $currency, string $field): void
    {
        $max = (float) (config('contributions.public_max_amount.'.$currency) ?? 0);

        if ($max > 0 && $amount > $max) {
            throw ValidationException::withMessages([
                $field => 'Montant trop eleve pour une contribution publique ; passez par un encaissement authentifie.',
            ]);
        }
    }
}
