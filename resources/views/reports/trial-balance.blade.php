<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Balance SYCEBNL</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #14231f; }
        h1 { font-size: 22px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d9e3dd; padding: 6px; text-align: left; }
        th { background: #edf4f0; }
        td.num { text-align: right; }
    </style>
</head>
<body>
    <h1>Balance SYCEBNL</h1>
    <table>
        <thead><tr><th>Compte</th><th>Libelle</th><th>Debit</th><th>Credit</th></tr></thead>
        <tbody>
            @foreach ($accounts as $account)
                <tr>
                    <td>{{ $account['code'] }}</td>
                    <td>{{ $account['label'] }}</td>
                    <td class="num">{{ number_format($account['debit'], 2, ',', ' ') }}</td>
                    <td class="num">{{ number_format($account['credit'], 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
