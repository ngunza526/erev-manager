<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrialBalanceExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $accounts)
    {
    }

    public function collection(): Collection
    {
        return $this->accounts->map(fn (array $account) => [
            $account['code'],
            $account['label'],
            $account['debit'],
            $account['credit'],
        ]);
    }

    public function headings(): array
    {
        return ['Compte', 'Libelle', 'Debit', 'Credit'];
    }
}
