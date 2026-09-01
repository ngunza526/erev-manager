<?php

namespace App\Http\Controllers;

use App\Models\Church;
use App\Models\ChurchEvent;
use App\Models\EventRegistration;
use App\Models\Visitor;
use App\Services\Accounting\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public function storeDonation(Request $request, Church $church, AccountingService $accounting): RedirectResponse
    {
        $data = $request->validate([
            'giver_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['dime', 'offrande', 'don'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'exchange_rate' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'bank', 'card', 'mobile_money', 'mpesa', 'airtel_money', 'orange_money'])],
            'phone' => ['nullable', 'string', 'max:80'],
        ]);

        $accounting->recordCollection([
            'church_id' => $church->id,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'exchange_rate' => $data['exchange_rate'],
            'cash_account_code' => $this->cashAccount($data['payment_method']),
            'description' => 'Don public '.($data['giver_name'] ?: 'anonyme'),
        ]);

        return back()->with('success', 'Contribution recue et comptabilisee.');
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

    public function storeEvent(Request $request, ChurchEvent $event, AccountingService $accounting): RedirectResponse
    {
        $data = $request->validate([
            'attendee_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'exchange_rate' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'bank', 'card', 'mobile_money', 'mpesa', 'airtel_money', 'orange_money'])],
        ]);

        $entry = null;
        if ((float) ($data['amount_paid'] ?? 0) > 0) {
            $entry = $accounting->recordBalancedEntry([
                'church_id' => $event->church_id,
                'type' => 'event_registration',
                'entry_date' => now()->toDateString(),
                'description' => "Inscription publique {$event->title}",
                'currency' => $data['currency'],
                'exchange_rate' => $data['exchange_rate'],
                'lines' => [
                    ['account_code' => $this->cashAccount($data['payment_method']), 'label' => 'Encaissement inscription publique', 'debit' => $data['amount_paid'], 'credit' => 0],
                    ['account_code' => '704', 'label' => 'Revenu evenement', 'debit' => 0, 'credit' => $data['amount_paid']],
                ],
            ]);
        }

        EventRegistration::create([
            'church_id' => $event->church_id,
            'church_event_id' => $event->id,
            'journal_entry_id' => $entry?->id,
            'attendee_name' => $data['attendee_name'],
            'phone' => $data['phone'] ?? null,
            'ticket_code' => 'PUB-'.strtoupper(Str::random(8)),
            'currency' => $data['currency'],
            'amount_paid' => $data['amount_paid'] ?? 0,
            'exchange_rate' => $data['exchange_rate'],
            'payment_method' => $data['payment_method'],
            'check_in_status' => 'registered',
        ]);

        $event->increment('registrations_count');

        return back()->with('success', 'Inscription confirmee.');
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
