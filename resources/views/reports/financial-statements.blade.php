<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bilan OHADA et formation du resultat</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #14231f; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        h2 { font-size: 16px; margin-top: 18px; }
        h3 { font-size: 13px; margin: 12px 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #d9e3dd; padding: 6px; text-align: left; }
        th { background: #edf4f0; }
        td.num { text-align: right; }
        .total { font-weight: bold; background: #f7faf8; }
        .muted { color: #63736d; }
    </style>
</head>
<body>
    <h1>Etats financiers OHADA / SYCEBNL</h1>
    <p class="muted">Bilan synthetique et tableau de formation du resultat generes depuis les ecritures validees.</p>

    <h2>Tableau de formation du resultat</h2>
    <h3>Produits classe 7</h3>
    @include('reports.partials.statement-section', ['rows' => $statements['income_statement']['revenues']['rows'], 'total' => $statements['income_statement']['revenues_total']])
    <h3>Charges classe 6</h3>
    @include('reports.partials.statement-section', ['rows' => $statements['income_statement']['expenses']['rows'], 'total' => $statements['income_statement']['expenses_total']])
    <table>
        <tr class="total"><td>Resultat net</td><td class="num">{{ number_format($statements['income_statement']['net_result'], 2, ',', ' ') }}</td></tr>
    </table>

    <h2>Bilan synthetique</h2>
    <h3>Actif</h3>
    @foreach ($statements['balance_sheet']['assets'] as $label => $section)
        <h3>{{ ucfirst(str_replace('_', ' ', $label)) }}</h3>
        @include('reports.partials.statement-section', ['rows' => $section['rows'], 'total' => $section['total']])
    @endforeach
    <table>
        <tr class="total"><td>Total actif</td><td class="num">{{ number_format($statements['balance_sheet']['assets_total'], 2, ',', ' ') }}</td></tr>
    </table>

    <h3>Passif</h3>
    @foreach ($statements['balance_sheet']['liabilities'] as $label => $section)
        <h3>{{ ucfirst(str_replace('_', ' ', $label)) }}</h3>
        @include('reports.partials.statement-section', ['rows' => $section['rows'], 'total' => $section['total']])
    @endforeach
    <table>
        <tr><td>Resultat net affecte au passif</td><td class="num">{{ number_format($statements['balance_sheet']['net_result'], 2, ',', ' ') }}</td></tr>
        <tr class="total"><td>Total passif</td><td class="num">{{ number_format($statements['balance_sheet']['liabilities_total'], 2, ',', ' ') }}</td></tr>
        <tr><td>Ecart de controle actif - passif</td><td class="num">{{ number_format($statements['balance_sheet']['control_gap'], 2, ',', ' ') }}</td></tr>
    </table>

    <h2>Annexes SYCEBNL</h2>
    <h3>Tresorerie banque, caisse et mobile money</h3>
    @include('reports.partials.statement-section', ['rows' => $statements['annexes']['cash_and_bank']['rows'], 'total' => $statements['annexes']['cash_and_bank']['total']])
    <h3>Fonds dedies et reserves affectees</h3>
    @include('reports.partials.statement-section', ['rows' => $statements['annexes']['restricted_funds']['rows'], 'total' => $statements['annexes']['restricted_funds']['total']])
    <h3>Creances membres et tiers</h3>
    @include('reports.partials.statement-section', ['rows' => $statements['annexes']['receivables']['rows'], 'total' => $statements['annexes']['receivables']['total']])
    <h3>Dettes fournisseurs, personnel, sociales, fiscales et partenaires</h3>
    @include('reports.partials.statement-section', ['rows' => $statements['annexes']['payables']['rows'], 'total' => $statements['annexes']['payables']['total']])
    <table>
        <tr><td>Total actif controle</td><td class="num">{{ number_format($statements['annexes']['control']['assets_total'], 2, ',', ' ') }}</td></tr>
        <tr><td>Total passif controle</td><td class="num">{{ number_format($statements['annexes']['control']['liabilities_total'], 2, ',', ' ') }}</td></tr>
        <tr class="total"><td>Ecart de controle</td><td class="num">{{ number_format($statements['annexes']['control']['control_gap'], 2, ',', ' ') }}</td></tr>
    </table>
</body>
</html>
