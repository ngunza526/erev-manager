<script setup>
import { reactive, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

const props = defineProps({ churches: Array, cashAccounts: Array, collectionTypes: Array, entries: Object });
const page = usePage();
const defaultExchangeRate = Number(page.props.rdc?.default_exchange_rate || 1);

const typeByCode = (code) => props.collectionTypes.find((type) => type.code === code);

const form = reactive({
  church_id: '',
  type: props.collectionTypes[0]?.code ?? 'dime',
  cash_account_code: props.collectionTypes[0]?.default_cash_account_code ?? '511',
  amount: '',
  currency: 'USD',
  exchange_rate: defaultExchangeRate,
  description: '',
});

// Chaque type de collecte a sa propre caisse/compte par defaut : on la
// repropose des que le type change, tout en laissant la main pour la
// remplacer par une autre caisse/compte au besoin.
watch(() => form.type, (type) => {
  form.cash_account_code = typeByCode(type)?.default_cash_account_code ?? form.cash_account_code;
});

const submit = () => router.post('/comptabilite/collectes', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Comptabilite — Collecte">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvelle collecte</h2>
        <div class="acct-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <label>
            Type de collecte
            <select v-model="form.type">
              <option v-for="type in collectionTypes" :key="type.code" :value="type.code">{{ type.label }}</option>
            </select>
          </label>
          <label>
            Caisse / compte
            <select v-model="form.cash_account_code" required>
              <option v-for="account in cashAccounts" :key="account.id" :value="account.code">{{ account.code }} - {{ account.label }}</option>
            </select>
          </label>
          <TextInput v-model="form.amount" label="Montant" type="number" required />
          <label>Devise<select v-model="form.currency"><option>USD</option><option>CDF</option></select></label>
          <TextInput v-model="form.exchange_rate" label="Taux CDF/USD" type="number" required />
          <TextInput v-model="form.description" label="Description" />
        </div>
        <button class="btn">Comptabiliser</button>
      </form>

      <section class="panel">
        <h2>Collectes recentes <small>{{ entries.total }} au total</small></h2>
        <div class="acct-table-wrap">
          <table class="acct-table">
            <thead>
              <tr><th>Reference</th><th>Type</th><th>Eglise</th><th>Devise</th><th>Lignes</th></tr>
            </thead>
            <tbody>
              <tr v-for="entry in entries.data" :key="entry.id">
                <td>{{ entry.reference }}</td>
                <td><strong>{{ typeByCode(entry.type)?.label ?? entry.type }}</strong></td>
                <td>{{ entry.church?.designation ?? '—' }}</td>
                <td>{{ entry.currency }}</td>
                <td>
                  <div class="acct-lines">
                    <span v-for="line in entry.lines" :key="line.id" class="tag">
                      {{ line.account?.code }} {{ line.label }} · D{{ line.debit }} / C{{ line.credit }}
                    </span>
                  </div>
                </td>
              </tr>
              <tr v-if="!entries.data.length"><td colspan="5">Aucune collecte.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="entries" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.acct-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.acct-table-wrap { overflow-x: auto; }
.acct-table { width: 100%; border-collapse: collapse; }
.acct-table th,
.acct-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  vertical-align: middle;
}
.acct-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; white-space: nowrap; }
.acct-table tr:last-child td { border-bottom: 0; }
.acct-lines { display: flex; flex-wrap: wrap; gap: 6px; }
</style>
