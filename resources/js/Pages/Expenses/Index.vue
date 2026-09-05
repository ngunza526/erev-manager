<script setup>
import { computed, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

const props = defineProps({ churches: Array, budgets: Array, expenses: Object });
const page = usePage();
const defaultExchangeRate = Number(page.props.rdc?.default_exchange_rate || 1);
const paymentMethods = computed(() => page.props.rdc?.payment_methods || { cash: 'Caisse', bank: 'Banque', card: 'Carte bancaire', mobile_money: 'Mobile Money' });

const form = reactive({
  church_id: '',
  budget_id: '',
  description: '',
  vendor: '',
  category: 'fonctionnement',
  currency: 'USD',
  amount: '',
  exchange_rate: defaultExchangeRate,
  expense_date: new Date().toISOString().slice(0, 10),
  payment_method: 'cash',
  status: 'draft',
});
const availableBudgets = computed(() => props.budgets.filter((budget) => !form.church_id || Number(budget.church_id) === Number(form.church_id)));
const submit = () => router.post('/depenses', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Depenses">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvelle depense</h2>
        <div class="exp-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <label>
            Budget
            <select v-model="form.budget_id">
              <option value="">Sans budget</option>
              <option v-for="budget in availableBudgets" :key="budget.id" :value="budget.id">{{ budget.name }}</option>
            </select>
          </label>
          <TextInput v-model="form.description" label="Description" required />
          <TextInput v-model="form.vendor" label="Fournisseur" />
          <TextInput v-model="form.category" label="Categorie" required />
          <label>
            Devise
            <select v-model="form.currency"><option>USD</option><option>CDF</option></select>
          </label>
          <TextInput v-model="form.amount" label="Montant" type="number" required />
          <TextInput v-model="form.exchange_rate" label="Taux CDF/USD" type="number" required />
          <TextInput v-model="form.expense_date" label="Date" type="date" required />
          <label>
            Paiement
            <select v-model="form.payment_method"><option v-for="(label, code) in paymentMethods" :key="code" :value="code">{{ label }}</option></select>
          </label>
          <label>
            Statut
            <select v-model="form.status"><option>draft</option><option>approved</option><option>paid</option></select>
          </label>
        </div>
        <button class="btn">{{ form.status === 'paid' ? 'Enregistrer et comptabiliser' : 'Enregistrer sans decaissement' }}</button>
      </form>

      <section class="panel">
        <h2>Depenses <small>{{ expenses.total }} au total</small></h2>
        <div class="exp-table-wrap">
          <table class="exp-table">
            <thead>
              <tr><th>Description</th><th>Eglise</th><th>Fournisseur</th><th>Date</th><th>Montant</th><th>Paiement</th><th>Statut</th></tr>
            </thead>
            <tbody>
              <tr v-for="expense in expenses.data" :key="expense.id">
                <td><strong>{{ expense.description }}</strong></td>
                <td>{{ expense.church?.designation ?? '—' }}</td>
                <td>{{ expense.vendor || 'sans fournisseur' }}</td>
                <td>{{ expense.expense_date }}</td>
                <td>{{ expense.amount }} {{ expense.currency }}</td>
                <td>{{ expense.payment_method }}</td>
                <td>
                  <span class="tag" :class="{ gold: expense.status !== 'paid' }">{{ expense.status }}</span>
                  <span v-if="expense.journal_entry" class="tag">JRN {{ expense.journal_entry.reference }}</span>
                </td>
              </tr>
              <tr v-if="!expenses.data.length"><td colspan="7">Aucune depense.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="expenses" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.exp-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.exp-table-wrap { overflow-x: auto; }
.exp-table { width: 100%; border-collapse: collapse; }
.exp-table th,
.exp-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  white-space: nowrap;
}
.exp-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; }
.exp-table tr:last-child td { border-bottom: 0; }
</style>
