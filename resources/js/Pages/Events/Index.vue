<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ churches: Array, events: Object });

const form = reactive({
  church_id: '',
  title: '',
  event_type: 'conference',
  starts_at: '',
  ends_at: '',
  venue: '',
  currency: 'CDF',
  ticket_price: 0,
  capacity: '',
  registrations_count: 0,
  is_public: true,
});

const submit = () => router.post('/evenements', form, { preserveScroll: true });
const formatDate = (value) => (value ? new Date(value).toLocaleString('fr-FR') : '—');
</script>

<template>
  <AppLayout title="Evenements">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvel evenement</h2>
        <div class="evt-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <TextInput v-model="form.title" label="Titre" required />
          <TextInput v-model="form.event_type" label="Type" required />
          <TextInput v-model="form.venue" label="Lieu" required />
          <TextInput v-model="form.starts_at" label="Debut" type="datetime-local" required />
          <TextInput v-model="form.ends_at" label="Fin" type="datetime-local" />
          <label>
            Devise
            <select v-model="form.currency"><option>USD</option><option>CDF</option></select>
          </label>
          <TextInput v-model="form.ticket_price" label="Prix ticket" type="number" />
          <TextInput v-model="form.capacity" label="Capacite" type="number" />
        </div>
        <button class="btn">Creer</button>
      </form>

      <section class="panel">
        <h2>Calendrier <small>{{ events.total }} au total</small></h2>
        <div class="evt-table-wrap">
          <table class="evt-table">
            <thead>
              <tr><th>Titre</th><th>Eglise</th><th>Type</th><th>Lieu</th><th>Debut</th><th>Prix</th><th>Inscrits</th></tr>
            </thead>
            <tbody>
              <tr v-for="event in events.data" :key="event.id">
                <td><strong>{{ event.title }}</strong></td>
                <td>{{ event.church?.designation ?? '—' }}</td>
                <td>{{ event.event_type }}</td>
                <td>{{ event.venue }}</td>
                <td>{{ formatDate(event.starts_at) }}</td>
                <td>{{ event.ticket_price }} {{ event.currency }}</td>
                <td>{{ event.registrations_count }}</td>
              </tr>
              <tr v-if="!events.data.length"><td colspan="7">Aucun evenement.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="events" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.evt-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.evt-table-wrap { overflow-x: auto; }
.evt-table { width: 100%; border-collapse: collapse; }
.evt-table th,
.evt-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  white-space: nowrap;
}
.evt-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; }
.evt-table tr:last-child td { border-bottom: 0; }
</style>
