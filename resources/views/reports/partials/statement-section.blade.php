<table>
    <thead><tr><th>Compte</th><th>Libelle</th><th>Montant</th></tr></thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['code'] }}</td>
                <td>{{ $row['label'] }}</td>
                <td class="num">{{ number_format($row['amount'], 2, ',', ' ') }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">Aucun mouvement valide.</td></tr>
        @endforelse
        <tr class="total"><td colspan="2">Total</td><td class="num">{{ number_format($total, 2, ',', ' ') }}</td></tr>
    </tbody>
</table>
