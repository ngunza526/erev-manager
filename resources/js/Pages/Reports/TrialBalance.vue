<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({ accounts: Array, totals: Object, generatedAt: String });

const isBalanced = computed(() => Math.abs(props.totals.debit - props.totals.credit) < 0.01);

const fmt = (value) => Number(value || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
  <AppLayout title="Balance generale">
    <div class="grid">
      <section class="panel">
        <div class="report-head">
          <div>
            <h2>Balance generale de tous les comptes</h2>
            <small>Generee le {{ generatedAt }} · {{ accounts.length }} comptes</small>
          </div>
          <div class="tags no-print">
            <button class="btn secondary" type="button" @click="window.print()">Imprimer</button>
            <a class="btn secondary" href="/rapports/balance.pdf">Telecharger PDF</a>
            <a class="btn secondary" href="/rapports/balance.xlsx">Telecharger Excel</a>
          </div>
        </div>

        <div class="report-table-wrap">
          <table class="report-table">
            <thead>
              <tr>
                <th>Compte</th>
                <th>Libelle</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
                <th class="num">Solde</th>
                <th class="no-print">Grand livre</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="account in accounts" :key="account.id">
                <td>{{ account.code }}</td>
                <td>{{ account.label }}</td>
                <td class="num">{{ fmt(account.debit) }}</td>
                <td class="num">{{ fmt(account.credit) }}</td>
                <td class="num">{{ fmt(account.balance) }}</td>
                <td class="no-print">
                  <Link class="icon-action is-blue" :href="`/rapports/grand-livre/${account.id}`" title="Voir le grand livre du compte">
                    <i class="bi bi-journal-text" />
                  </Link>
                </td>
              </tr>
              <tr v-if="!accounts.length"><td colspan="6">Aucun compte.</td></tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="2">Total</td>
                <td class="num">{{ fmt(totals.debit) }}</td>
                <td class="num">{{ fmt(totals.credit) }}</td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <p class="report-check" :class="{ 'is-off': !isBalanced }">
          {{ isBalanced ? 'Balance equilibree : total debit = total credit.' : 'Ecart detecte entre le total debit et le total credit — verifier les ecritures.' }}
        </p>
      </section>
    </div>
  </AppLayout>
</template>
