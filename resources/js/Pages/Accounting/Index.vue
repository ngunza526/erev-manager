<script setup>
import { computed, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ churches: Array, accounts: Array, entries: Object });
const page = usePage();
const defaultExchangeRate = Number(page.props.rdc?.default_exchange_rate || 1);

const form = reactive({ church_id: '', type: 'dime', amount: '', currency: 'USD', exchange_rate: defaultExchangeRate, description: '' });
const submit = () => router.post('/comptabilite/collectes', form, { preserveScroll: true });

const manual = reactive({
  church_id: '',
  entry_date: new Date().toISOString().slice(0, 10),
  description: '',
  currency: 'USD',
  exchange_rate: defaultExchangeRate,
  lines: [
    { account_code: '511', label: '', debit: 0, credit: 0 },
    { account_code: '601', label: '', debit: 0, credit: 0 },
  ],
});
const debitTotal = computed(() => manual.lines.reduce((sum, line) => sum + Number(line.debit || 0), 0));
const creditTotal = computed(() => manual.lines.reduce((sum, line) => sum + Number(line.credit || 0), 0));
const isBalanced = computed(() => debitTotal.value > 0 && debitTotal.value === creditTotal.value);
const addLine = () => manual.lines.push({ account_code: '', label: '', debit: 0, credit: 0 });
const removeLine = (index) => manual.lines.splice(index, 1);
const submitManual = () => router.post('/comptabilite/ecritures', manual, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Comptabilite">
    <div class="grid">
      <div class="acct-forms">
        <form class="panel form" @submit.prevent="submit">
          <h2>Collecte</h2>
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <label>
            Type
            <select v-model="form.type"><option value="dime">Dime</option><option value="offrande">Offrande</option><option value="don">Don</option></select>
          </label>
          <div class="row">
            <TextInput v-model="form.amount" label="Montant" type="number" required />
            <label>Devise<select v-model="form.currency"><option>USD</option><option>CDF</option></select></label>
          </div>
          <TextInput v-model="form.exchange_rate" label="Taux CDF/USD" type="number" required />
          <TextInput v-model="form.description" label="Description" />
          <button class="btn">Comptabiliser</button>
        </form>

        <form class="panel form" @submit.prevent="submitManual">
          <h2>Saisie debit-credit</h2>
          <label>
            Eglise
            <select v-model="manual.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <div class="row">
            <TextInput v-model="manual.entry_date" label="Date" type="date" required />
            <label>Devise<select v-model="manual.currency"><option>USD</option><option>CDF</option></select></label>
          </div>
          <TextInput v-model="manual.exchange_rate" label="Taux CDF/USD" type="number" required />
          <TextInput v-model="manual.description" label="Libelle de l'ecriture" required />
          <article v-for="(line, index) in manual.lines" :key="index" class="item">
            <div class="row">
              <label>Compte<select v-model="line.account_code" required><option value="">Choisir</option><option v-for="account in accounts" :key="account.id" :value="account.code">{{ account.code }} - {{ account.label }}</option></select></label>
              <TextInput v-model="line.label" label="Libelle ligne" required />
            </div>
            <div class="row">
              <TextInput v-model="line.debit" label="Debit" type="number" required />
              <TextInput v-model="line.credit" label="Credit" type="number" required />
            </div>
            <button v-if="manual.lines.length > 2" class="btn secondary" type="button" @click="removeLine(index)">Retirer</button>
          </article>
          <div class="tags">
            <span class="tag">Debit {{ debitTotal }}</span>
            <span class="tag">Credit {{ creditTotal }}</span>
            <span :class="['tag', isBalanced ? 'gold' : '']">{{ isBalanced ? 'equilibree' : 'a equilibrer' }}</span>
          </div>
          <button class="btn secondary" type="button" @click="addLine">Ajouter une ligne</button>
          <button class="btn" :disabled="!isBalanced">Valider l'ecriture</button>
        </form>
      </div>

      <section class="panel">
        <h2>Journal <small>{{ entries.total }} au total</small></h2>
        <div class="tags acct-reports">
          <a class="btn secondary" href="/rapports/balance.pdf">Balance PDF</a>
          <a class="btn secondary" href="/rapports/balance.xlsx">Balance Excel</a>
          <a class="btn secondary" href="/rapports/etats-ohada.pdf">Etats OHADA PDF</a>
          <a class="btn secondary" href="/rapports/etats-ohada.xlsx">Etats OHADA Excel</a>
        </div>
        <div class="acct-table-wrap">
          <table class="acct-table">
            <thead>
              <tr><th>Reference</th><th>Description</th><th>Eglise</th><th>Devise</th><th>Statut</th><th>Lignes</th></tr>
            </thead>
            <tbody>
              <tr v-for="entry in entries.data" :key="entry.id">
                <td>{{ entry.reference }}</td>
                <td><strong>{{ entry.description }}</strong></td>
                <td>{{ entry.church?.designation ?? '—' }}</td>
                <td>{{ entry.currency }}</td>
                <td><span class="tag" :class="{ gold: entry.status !== 'validee' }">{{ entry.status }}</span></td>
                <td>
                  <div class="acct-lines">
                    <span v-for="line in entry.lines" :key="line.id" class="tag">
                      {{ line.account?.code }} {{ line.label }} · D{{ line.debit }} / C{{ line.credit }}
                    </span>
                  </div>
                </td>
              </tr>
              <tr v-if="!entries.data.length"><td colspan="6">Aucune ecriture.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="entries" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.acct-forms {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  align-items: start;
}

@media (max-width: 860px) {
  .acct-forms { grid-template-columns: 1fr; }
}

.acct-reports { margin-bottom: 12px; }

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
