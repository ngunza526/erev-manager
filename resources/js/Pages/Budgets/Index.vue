<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

defineProps({ churches: Array, budgets: Object });
const form = reactive({ church_id: '', name: '', department: '', currency: 'USD', amount: '', period_starts_at: '', period_ends_at: '', status: 'active' });
const submit = () => router.post('/budgets', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Budgets">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau budget</h2>
        <label>Eglise<select v-model="form.church_id" required><option value="">Choisir</option><option v-for="c in churches" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <TextInput v-model="form.name" label="Nom du budget" required />
        <TextInput v-model="form.department" label="Departement" />
        <div class="row"><label>Devise<select v-model="form.currency"><option>USD</option><option>CDF</option></select></label><TextInput v-model="form.amount" label="Montant" type="number" required /></div>
        <div class="row"><TextInput v-model="form.period_starts_at" label="Debut periode" type="date" required /><TextInput v-model="form.period_ends_at" label="Fin periode" type="date" required /></div>
        <label>Statut<select v-model="form.status"><option>draft</option><option>active</option><option>closed</option></select></label>
        <button class="btn">Creer</button>
      </form>
      <section class="panel">
        <h2>Suivi budgetaire</h2>
        <div class="list">
          <article v-for="budget in budgets.data" :key="budget.id" class="item">
            <header><strong>{{ budget.name }}</strong><small>{{ budget.status }}</small></header>
            <small>{{ budget.church?.designation }} · {{ budget.department || 'general' }}</small>
            <div class="tags"><span class="tag">{{ budget.amount }} {{ budget.currency }}</span><span class="tag gold">depense {{ budget.spent_amount || 0 }}</span></div>
          </article>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
