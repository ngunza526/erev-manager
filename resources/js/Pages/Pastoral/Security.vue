<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ churches: Array, items: Object });

const form = reactive({
  church_id: '',
  title: '',
  incident_type: 'general',
  severity: 'medium',
  occurred_at: '',
  reported_by: '',
  status: 'open',
  description: '',
});

const submit = () => router.post('/incidents', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Securite et incidents">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvel incident</h2>
        <div class="pst-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <TextInput v-model="form.title" label="Titre" required />
          <TextInput v-model="form.incident_type" label="Type" required />
          <TextInput v-model="form.severity" label="Gravite" required />
          <TextInput v-model="form.occurred_at" label="Date et heure" type="datetime-local" required />
          <TextInput v-model="form.reported_by" label="Rapporte par" required />
          <TextInput v-model="form.status" label="Statut" required />
          <TextInput v-model="form.description" label="Description" required />
        </div>
        <button class="btn">Enregistrer</button>
      </form>

      <section class="panel">
        <h2>Journal securite <small>{{ items.total }} au total</small></h2>
        <div class="pst-table-wrap">
          <table class="pst-table">
            <thead>
              <tr><th>Titre</th><th>Eglise</th><th>Type</th><th>Gravite</th><th>Date</th><th>Rapporte par</th><th>Statut</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in items.data" :key="item.id">
                <td><strong>{{ item.title }}</strong></td>
                <td>{{ item.church?.designation ?? '—' }}</td>
                <td>{{ item.incident_type }}</td>
                <td><span class="tag gold">{{ item.severity }}</span></td>
                <td>{{ item.occurred_at }}</td>
                <td>{{ item.reported_by }}</td>
                <td><span class="tag">{{ item.status }}</span></td>
              </tr>
              <tr v-if="!items.data.length"><td colspan="7">Aucun incident.</td></tr>
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
