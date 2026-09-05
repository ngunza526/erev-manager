<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ churches: Array, groups: Object });

const form = reactive({
  church_id: '',
  name: '',
  group_type: 'cellule',
  leader_name: '',
  meeting_day: '',
  district: '',
  city: '',
  members_count: 0,
});

const submit = () => router.post('/groupes', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Groupes et cellules">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau groupe</h2>
        <div class="grp-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <TextInput v-model="form.name" label="Nom" required />
          <TextInput v-model="form.group_type" label="Type" required />
          <TextInput v-model="form.leader_name" label="Responsable" required />
          <TextInput v-model="form.meeting_day" label="Jour reunion" />
          <TextInput v-model="form.members_count" label="Membres" type="number" />
          <TextInput v-model="form.district" label="Quartier" />
          <TextInput v-model="form.city" label="Ville" />
        </div>
        <button class="btn">Creer</button>
      </form>

      <section class="panel">
        <h2>Groupes actifs <small>{{ groups.total }} au total</small></h2>
        <div class="grp-table-wrap">
          <table class="grp-table">
            <thead>
              <tr><th>Nom</th><th>Eglise</th><th>Type</th><th>Responsable</th><th>Localisation</th><th>Reunion</th><th>Membres</th></tr>
            </thead>
            <tbody>
              <tr v-for="group in groups.data" :key="group.id">
                <td><strong>{{ group.name }}</strong></td>
                <td>{{ group.church?.designation ?? '—' }}</td>
                <td>{{ group.group_type }}</td>
                <td>{{ group.leader_name }}</td>
                <td>{{ group.district }}, {{ group.city }}</td>
                <td>{{ group.meeting_day || 'a definir' }}</td>
                <td>{{ group.members_count }}</td>
              </tr>
              <tr v-if="!groups.data.length"><td colspan="7">Aucun groupe.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="groups" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.grp-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.grp-table-wrap { overflow-x: auto; }
.grp-table { width: 100%; border-collapse: collapse; }
.grp-table th,
.grp-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  white-space: nowrap;
}
.grp-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; }
.grp-table tr:last-child td { border-bottom: 0; }
</style>
