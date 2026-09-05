<script setup>
import { reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({ account: Object, accounts: Array, filters: Object, rows: Array, totals: Object });

const filters = reactive({ from: props.filters?.from || '', to: props.filters?.to || '' });
const selectedAccount = computed({
  get: () => props.account.id,
  set: (id) => router.get(`/rapports/grand-livre/${id}`, cleanFilters(), { preserveState: true }),
});

const cleanFilters = () => {
  const query = {};
  if (filters.from) query.from = filters.from;
  if (filters.to) query.to = filters.to;
  return query;
};

const applyFilters = () => router.get(`/rapports/grand-livre/${props.account.id}`, cleanFilters(), { preserveState: true });
const resetFilters = () => { filters.from = ''; filters.to = ''; router.get(`/rapports/grand-livre/${props.account.id}`); };

const fmt = (value) => Number(value || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
  <AppLayout title="Grand livre">
    <div class="grid">
      <section class="panel">
        <div class="report-head">
          <div>
            <h2>Grand livre — {{ account.code }} {{ account.label }}</h2>
            <small>Solde normal : {{ account.normal_side === 'debit' ? 'debiteur' : 'crediteur' }} · {{ rows.length }} mouvements</small>
          </div>
          <div class="tags no-print">
            <button class="btn secondary" type="button" @click="window.print()">Imprimer</button>
          </div>
        </div>

        <form class="ledger-filters no-print" @submit.prevent="applyFilters">
          <label>
            Compte
            <select v-model="selectedAccount">
              <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.code }} — {{ acc.label }}</option>
            </select>
          </label>
          <TextInput v-model="filters.from" label="Du" type="date" />
          <TextInput v-model="filters.to" label="Au" type="date" />
          <div class="ledger-filter-actions">
            <button class="btn secondary" type="submit">Filtrer</button>
            <button class="btn secondary" type="button" @click="resetFilters">Reinitialiser</button>
          </div>
        </form>

        <div class="report-table-wrap">
          <table class="report-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Eglise</th>
                <th>Libelle</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
                <th class="num">Solde</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, index) in rows" :key="index">
                <td>{{ row.date }}</td>
                <td>{{ row.reference }}</td>
                <td>{{ row.church ?? '—' }}</td>
                <td>{{ row.label }}</td>
                <td class="num">{{ fmt(row.debit) }}</td>
                <td class="num">{{ fmt(row.credit) }}</td>
                <td class="num">{{ fmt(row.balance) }}</td>
              </tr>
              <tr v-if="!rows.length"><td colspan="7">Aucun mouvement pour ce compte sur la periode.</td></tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="4">Total</td>
                <td class="num">{{ fmt(totals.debit) }}</td>
                <td class="num">{{ fmt(totals.credit) }}</td>
                <td class="num">{{ fmt(totals.balance) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <p class="report-check">
          Solde cumule du compte {{ account.code }} sur la periode : {{ fmt(totals.balance) }}
        </p>
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.ledger-filters {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
  align-items: end;
  margin-bottom: 16px;
}
.ledger-filter-actions { display: flex; gap: 8px; align-items: end; }
</style>
