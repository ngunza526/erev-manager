<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinancialStatementExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly array $statements)
    {
    }

    public function collection(): Collection
    {
        $rows = collect();
        $income = $this->statements['income_statement'];
        $balance = $this->statements['balance_sheet'];

        $rows->push(['Formation du resultat', '', '']);
        $this->appendSection($rows, 'Produits classe 7', $income['revenues']['rows']);
        $rows->push(['Total produits', '', $income['revenues_total']]);
        $this->appendSection($rows, 'Charges classe 6', $income['expenses']['rows']);
        $rows->push(['Total charges', '', $income['expenses_total']]);
        $rows->push(['Resultat net', '', $income['net_result']]);
        $rows->push(['', '', '']);

        $rows->push(['Bilan OHADA/SYCEBNL', '', '']);
        foreach ($balance['assets'] as $label => $section) {
            $this->appendSection($rows, 'Actif - '.str_replace('_', ' ', $label), $section['rows']);
            $rows->push(['Total '.$label, '', $section['total']]);
        }
        $rows->push(['Total actif', '', $balance['assets_total']]);

        foreach ($balance['liabilities'] as $label => $section) {
            $this->appendSection($rows, 'Passif - '.str_replace('_', ' ', $label), $section['rows']);
            $rows->push(['Total '.$label, '', $section['total']]);
        }
        $rows->push(['Resultat net au passif', '', $balance['net_result']]);
        $rows->push(['Total passif', '', $balance['liabilities_total']]);
        $rows->push(['Ecart de controle', '', $balance['control_gap']]);
        $rows->push(['', '', '']);

        $rows->push(['Annexes SYCEBNL', '', '']);
        foreach ([
            'Tresorerie banque caisse mobile money' => 'cash_and_bank',
            'Fonds dedies et reserves affectees' => 'restricted_funds',
            'Creances membres et tiers' => 'receivables',
            'Dettes fournisseurs personnel sociales fiscales partenaires' => 'payables',
        ] as $label => $key) {
            $this->appendSection($rows, $label, $this->statements['annexes'][$key]['rows']);
            $rows->push(['Total '.$label, '', $this->statements['annexes'][$key]['total']]);
        }
        $rows->push(['Controle total actif', '', $this->statements['annexes']['control']['assets_total']]);
        $rows->push(['Controle total passif', '', $this->statements['annexes']['control']['liabilities_total']]);
        $rows->push(['Controle ecart actif-passif', '', $this->statements['annexes']['control']['control_gap']]);

        return $rows;
    }

    public function headings(): array
    {
        return ['Rubrique', 'Compte', 'Montant'];
    }

    private function appendSection(Collection $rows, string $title, Collection $accounts): void
    {
        $rows->push([$title, '', '']);
        foreach ($accounts as $account) {
            $rows->push([$account['label'], $account['code'], $account['amount']]);
        }
    }
}
