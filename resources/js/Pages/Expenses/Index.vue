<script setup>
import { computed, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({ churches: Array, budgets: Array, expenses: Object });
const page = usePage();
const defaultExchangeRate = Number(page.props.rdc?.default_exchange_rate || 1);
const paymentMethods = computed(() => page.props.rdc?.payment_methods || { cash: 'Caisse', bank: 'Banque', card: 'Carte bancaire', mobile_money: 'Mobile Money' });
const form = reactive({ church_id: '', budget_id: '', description: '', vendor: '', category: 'fonctionnement', currency: 'USD', amount: '', exchange_rate: defaultExchangeRate, expense_date: new Date().toISOString().slice(0, 10), payment_method: 'cash', status: 'draft' });
const availableBudgets = computed(() => props.budgets.filter((budget) => !form.church_id || Number(budget.church_id) === Number(form.church_id)));
const submit = () => router.post('/depenses', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Depenses">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvelle depense</h2>
        <label>Eglise<select v-model="form.church_id" required><option value="">Choisir</option><option v-for="c in churches" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <label>Budget<select v-model="form.budget_id"><option value="">Sans budget</option><option v-for="budget in availableBudgets" :key="budget.id" :value="budget.id">{{ budget.name }}</option></select></label>
        <TextInput v-model="form.description" label="Description" required />
        <div class="row"><TextInput v-model="form.vendor" label="Fournisseur" /><TextInput v-model="form.category" label="Categorie" required /></div>
        <div class="row"><label>Devise<select v-model="form.currency"><option>USD</option><option>CDF</option></select></label><TextInput v-model="form.amount" label="Montant" type="number" required /></div>
        <div class="row"><TextInput v-model="form.exchange_rate" label="Taux CDF/USD" type="number" required /><TextInput v-model="form.expense_date" label="Date" type="date" required /></div>
        <div class="row"><label>Paiement<select v-model="form.payment_method"><option v-for="(label, code) in paymentMethods" :key="code" :value="code">{{ label }}</option></select></label><label>Statut<select v-model="form.status"><option>draft</option><option>approved</option><option>paid</option></select></label></div>
        <button class="btn">{{ form.status === 'paid' ? 'Enregistrer et comptabiliser' : 'Enregistrer sans decaissement' }}</button>
      </form>
      <section class="panel">
        <h2>Depenses</h2>
        <div class="list">
          <article v-for="expense in expenses.data" :key="expense.id" class="item">
            <header><strong>{{ expense.description }}</strong><small>{{ expense.status }}</small></header>
            <small>{{ expense.church?.designation }} · {{ expense.vendor || 'sans fournisseur' }} · {{ expense.expense_date }}</small>
            <div class="tags">
              <span class="tag">{{ expense.amount }} {{ expense.currency }}</span>
              <span class="tag gold">{{ expense.payment_method }}</span>
              <span v-if="expense.journal_entry" class="tag">JRN {{ expense.journal_entry.reference }}</span>
            </div>
          </article>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
