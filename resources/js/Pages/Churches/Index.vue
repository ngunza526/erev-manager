<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ churches: Object, communities: Array });

const form = reactive({
  community_id: '',
  designation: '',
  address_number: '',
  address_avenue: '',
  address_district: '',
  address_city: '',
  address_province: '',
  address_country: 'RDC',
  email: '',
  phone: '',
});

const submit = () => router.post('/eglises', form, { preserveScroll: true });

const address = (church) => [
  church.address_number || 's/n',
  church.address_avenue,
  church.address_district,
].filter(Boolean).join(' ');
</script>

<template>
  <AppLayout title="Eglises">
    <div class="grid">
      <!-- 1. Formulaire pleine largeur -->
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvelle eglise</h2>
        <div class="egl-grid">
          <label>
            Communaute
            <select v-model="form.community_id" required>
              <option value="">Choisir</option>
              <option v-for="community in communities" :key="community.id" :value="community.id">{{ community.designation }}</option>
            </select>
          </label>
          <TextInput v-model="form.designation" label="Designation" required />
          <TextInput v-model="form.address_number" label="Numero" />
          <TextInput v-model="form.address_avenue" label="Avenue" />
          <TextInput v-model="form.address_district" label="Quartier" required />
          <TextInput v-model="form.address_city" label="Ville" required />
          <TextInput v-model="form.address_province" label="Province" required />
          <TextInput v-model="form.address_country" label="Pays" required />
          <TextInput v-model="form.email" label="Email" type="email" />
          <TextInput v-model="form.phone" label="Telephone" />
        </div>
        <button class="btn">Rattacher</button>
      </form>

      <!-- 2. Eglises locales : liste paginee pleine largeur -->
      <section class="panel">
        <h2>Eglises locales <small>{{ churches.total }} au total</small></h2>
        <div class="egl-table-wrap">
          <table class="egl-table">
            <thead>
              <tr><th>Designation</th><th>Communaute</th><th>Ville</th><th>Adresse</th><th>Membres</th><th>Telephone</th></tr>
            </thead>
            <tbody>
              <tr v-for="church in churches.data" :key="church.id">
                <td><strong>{{ church.designation }}</strong></td>
                <td>{{ church.community?.designation ?? '—' }}</td>
                <td>{{ church.address_city }}</td>
                <td>{{ address(church) }}</td>
                <td>{{ church.members_count }}</td>
                <td>{{ church.phone || '—' }}</td>
              </tr>
              <tr v-if="!churches.data.length"><td colspan="6">Aucune eglise.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="churches" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.egl-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.egl-table-wrap { overflow-x: auto; }
.egl-table { width: 100%; border-collapse: collapse; }
.egl-table th,
.egl-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
}
.egl-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; white-space: nowrap; }
.egl-table tr:last-child td { border-bottom: 0; }
</style>
