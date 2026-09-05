<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ churches: Array, budgets: Object });

const form = reactive({
  church_id: '',
  name: '',
  department: '',
  currency: 'USD',
  amount: '',
  period_starts_at: '',
  period_ends_at: '',
  status: 'active',
});

const submit = () => router.post('/budgets', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Budgets">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau budget</h2>
        <div class="bgt-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <TextInput v-model="form.name" label="Nom du budget" required />
          <TextInput v-model="form.department" label="Departement" />
          <label>
            Devise
            <select v-model="form.currency"><option>USD</option><option>CDF</option></select>
          </label>
          <TextInput v-model="form.amount" label="Montant" type="number" required />
          <TextInput v-model="form.period_starts_at" label="Debut periode" type="date" required />
          <TextInput v-model="form.period_ends_at" label="Fin periode" type="date" required />
          <label>
            Statut
            <select v-model="form.status"><option>draft</option><option>active</option><option>closed</option></select>
          </label>
        </div>
        <button class="btn">Creer</button>
      </form>

      <section class="panel">
        <h2>Suivi budgetaire <small>{{ budgets.total }} au total</small></h2>
        <div class="bgt-table-wrap">
          <table class="bgt-table">
            <thead>
              <tr><th>Nom</th><th>Eglise</th><th>Departement</th><th>Montant</th><th>Depense</th><th>Statut</th></tr>
            </thead>
            <tbody>
              <tr v-for="budget in budgets.data" :key="budget.id">
                <td><strong>{{ budget.name }}</strong></td>
                <td>{{ budget.church?.designation ?? '—' }}</td>
                <td>{{ budget.department || 'general' }}</td>
                <td>{{ budget.amount }} {{ budget.currency }}</td>
                <td>{{ budget.spent_amount || 0 }} {{ budget.currency }}</td>
                <td><span class="tag" :class="{ gold: budget.status !== 'active' }">{{ budget.status }}</span></td>
              </tr>
              <tr v-if="!budgets.data.length"><td colspan="6">Aucun budget.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="budgets" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.bgt-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.bgt-table-wrap { overflow-x: auto; }
.bgt-table { width: 100%; border-collapse: collapse; }
.bgt-table th,
.bgt-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  white-space: nowrap;
}
.bgt-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; }
.bgt-table tr:last-child td { border-bottom: 0; }
</style>
