<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ churches: Array, items: Object });

const form = reactive({
  church_id: '',
  full_name: '',
  phone: '',
  email: '',
  visit_source: 'culte',
  visited_at: new Date().toISOString().slice(0, 10),
  follow_up_status: 'a_relancer',
  notes: '',
});

const submit = () => router.post('/visiteurs', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Visiteurs">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau visiteur</h2>
        <div class="pst-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <TextInput v-model="form.full_name" label="Nom complet" required />
          <TextInput v-model="form.phone" label="Telephone" />
          <TextInput v-model="form.email" label="Email" type="email" />
          <TextInput v-model="form.visit_source" label="Source visite" required />
          <TextInput v-model="form.visited_at" label="Date visite" type="date" required />
          <TextInput v-model="form.follow_up_status" label="Statut relance" required />
          <TextInput v-model="form.notes" label="Notes" />
        </div>
        <button class="btn">Enregistrer</button>
      </form>

      <section class="panel">
        <h2>Suivi visiteurs <small>{{ items.total }} au total</small></h2>
        <div class="pst-table-wrap">
          <table class="pst-table">
            <thead>
              <tr><th>Nom</th><th>Eglise</th><th>Telephone</th><th>Date visite</th><th>Source</th><th>Statut</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in items.data" :key="item.id">
                <td><strong>{{ item.full_name }}</strong></td>
                <td>{{ item.church?.designation ?? '—' }}</td>
                <td>{{ item.phone || '—' }}</td>
                <td>{{ item.visited_at }}</td>
                <td>{{ item.visit_source }}</td>
                <td><span class="tag">{{ item.follow_up_status }}</span></td>
              </tr>
              <tr v-if="!items.data.length"><td colspan="6">Aucun visiteur.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="items" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.pst-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.pst-table-wrap { overflow-x: auto; }
.pst-table { width: 100%; border-collapse: collapse; }
.pst-table th,
.pst-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  white-space: nowrap;
}
.pst-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; }
.pst-table tr:last-child td { border-bottom: 0; }
</style>
