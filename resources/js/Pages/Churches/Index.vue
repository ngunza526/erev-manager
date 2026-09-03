<script setup>
import { reactive, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ churches: Object, communities: Array });

const blank = () => ({
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

const form = reactive(blank());
const editingId = ref(null);
const isEditing = computed(() => editingId.value !== null);

const startEdit = (church) => {
  editingId.value = church.id;
  Object.assign(form, blank(), {
    community_id: church.community_id ?? '',
    designation: church.designation ?? '',
    address_number: church.address_number ?? '',
    address_avenue: church.address_avenue ?? '',
    address_district: church.address_district ?? '',
    address_city: church.address_city ?? '',
    address_province: church.address_province ?? '',
    address_country: church.address_country ?? 'RDC',
    email: church.email ?? '',
    phone: church.phone ?? '',
  });
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelEdit = () => {
  editingId.value = null;
  Object.assign(form, blank());
};

const submit = () => {
  const done = { preserveScroll: true, onSuccess: cancelEdit };
  isEditing.value
    ? router.put(`/eglises/${editingId.value}`, form, done)
    : router.post('/eglises', form, { preserveScroll: true });
};

const remove = (church) => {
  if (window.confirm(`Supprimer l'eglise "${church.designation}" ?`)) {
    router.delete(`/eglises/${church.id}`, { preserveScroll: true, onSuccess: () => (church.id === editingId.value ? cancelEdit() : null) });
  }
};

const address = (church) => [
  church.address_number || 's/n',
  church.address_avenue,
  church.address_district,
].filter(Boolean).join(' ');
</script>

<template>
  <AppLayout title="Eglises">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>{{ isEditing ? 'Modifier l\'eglise' : 'Nouvelle eglise' }}</h2>
        <div class="egl-grid">
          <label>
            Communaute
            <select v-model="form.community_id" required :disabled="isEditing">
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
        <div class="egl-actions">
          <button class="btn">{{ isEditing ? 'Enregistrer' : 'Rattacher' }}</button>
          <button v-if="isEditing" class="btn secondary" type="button" @click="cancelEdit">Annuler</button>
        </div>
      </form>

      <section class="panel">
        <h2>Eglises locales <small>{{ churches.total }} au total</small></h2>
        <div class="egl-table-wrap">
          <table class="egl-table">
            <thead>
              <tr><th>Designation</th><th>Communaute</th><th>Ville</th><th>Adresse</th><th>Membres</th><th>Telephone</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="church in churches.data" :key="church.id" :class="{ 'is-editing': church.id === editingId }">
                <td><strong>{{ church.designation }}</strong></td>
                <td>{{ church.community?.designation ?? '—' }}</td>
                <td>{{ church.address_city }}</td>
                <td>{{ address(church) }}</td>
                <td>{{ church.members_count }}</td>
                <td>{{ church.phone || '—' }}</td>
                <td>
                  <div class="row-actions">
                    <button class="icon-action is-blue" type="button" title="Modifier" @click="startEdit(church)"><i class="bi bi-pencil-square" /></button>
                    <button class="icon-action is-red" type="button" title="Supprimer" @click="remove(church)"><i class="bi bi-trash" /></button>
                  </div>
                </td>
              </tr>
              <tr v-if="!churches.data.length"><td colspan="7">Aucune eglise.</td></tr>
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

.egl-actions { display: flex; gap: 10px; }

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
.egl-table tr.is-editing { background: var(--blue-soft); }

.btn.sm { min-height: 30px; padding: 0 10px; font-size: 12px; }
</style>
