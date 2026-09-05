<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ churches: Array, services: Object });

const form = reactive({
  church_id: '',
  title: '',
  service_type: 'culte',
  starts_at: '',
  ends_at: '',
  preacher: '',
  worship_leader: '',
  attendance_count: 0,
  notes: '',
});

const submit = () => router.post('/services', form, { preserveScroll: true });
const formatDate = (value) => (value ? new Date(value).toLocaleString('fr-FR') : '—');
</script>

<template>
  <AppLayout title="Services et cultes">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau service</h2>
        <div class="svc-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <TextInput v-model="form.title" label="Titre" required />
          <TextInput v-model="form.service_type" label="Type" required />
          <TextInput v-model="form.attendance_count" label="Presence" type="number" />
          <TextInput v-model="form.starts_at" label="Debut" type="datetime-local" required />
          <TextInput v-model="form.ends_at" label="Fin" type="datetime-local" />
          <TextInput v-model="form.preacher" label="Predicateur" />
          <TextInput v-model="form.worship_leader" label="Conducteur louange" />
          <TextInput v-model="form.notes" label="Notes" />
        </div>
        <button class="btn">Creer</button>
      </form>

      <section class="panel">
        <h2>Planning <small>{{ services.total }} au total</small></h2>
        <div class="svc-table-wrap">
          <table class="svc-table">
            <thead>
              <tr><th>Titre</th><th>Eglise</th><th>Type</th><th>Debut</th><th>Presence</th><th>Predicateur</th><th>Louange</th></tr>
            </thead>
            <tbody>
              <tr v-for="service in services.data" :key="service.id">
                <td><strong>{{ service.title }}</strong></td>
                <td>{{ service.church?.designation ?? '—' }}</td>
                <td>{{ service.service_type }}</td>
                <td>{{ formatDate(service.starts_at) }}</td>
                <td>{{ service.attendance_count }}</td>
                <td>{{ service.preacher || 'a definir' }}</td>
                <td>{{ service.worship_leader || 'a definir' }}</td>
              </tr>
              <tr v-if="!services.data.length"><td colspan="7">Aucun service planifie.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="services" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.svc-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.svc-table-wrap { overflow-x: auto; }
.svc-table { width: 100%; border-collapse: collapse; }
.svc-table th,
.svc-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  white-space: nowrap;
}
.svc-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; }
.svc-table tr:last-child td { border-bottom: 0; }
</style>
