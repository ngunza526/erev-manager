<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ churches: Array, items: Object });

const form = reactive({
  church_id: '',
  volunteer_name: '',
  team: 'accueil',
  role: '',
  service_date: new Date().toISOString().slice(0, 10),
  availability_status: 'confirmed',
  notes: '',
});

const submit = () => router.post('/volontaires', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Volontariat">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Affectation volontaire</h2>
        <div class="pst-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <TextInput v-model="form.volunteer_name" label="Volontaire" required />
          <TextInput v-model="form.team" label="Equipe" required />
          <TextInput v-model="form.role" label="Role" required />
          <TextInput v-model="form.service_date" label="Date service" type="date" required />
          <TextInput v-model="form.availability_status" label="Disponibilite" required />
          <TextInput v-model="form.notes" label="Notes" />
        </div>
        <button class="btn">Planifier</button>
      </form>

      <section class="panel">
        <h2>Planning volontaires <small>{{ items.total }} au total</small></h2>
        <div class="pst-table-wrap">
          <table class="pst-table">
            <thead>
              <tr><th>Volontaire</th><th>Eglise</th><th>Equipe</th><th>Role</th><th>Date service</th><th>Disponibilite</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in items.data" :key="item.id">
                <td><strong>{{ item.volunteer_name }}</strong></td>
                <td>{{ item.church?.designation ?? '—' }}</td>
                <td>{{ item.team }}</td>
                <td>{{ item.role }}</td>
                <td>{{ item.service_date }}</td>
                <td><span class="tag">{{ item.availability_status }}</span></td>
              </tr>
              <tr v-if="!items.data.length"><td colspan="6">Aucun volontaire.</td></tr>
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
