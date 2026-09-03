<script setup>
import { reactive, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ communities: Object });

const blank = () => ({
  designation: '',
  headquarters_number: '',
  headquarters_avenue: '',
  headquarters_district: '',
  headquarters_city: '',
  headquarters_province: '',
  headquarters_country: 'RDC',
  authorization_number: '',
  email: '',
  website: '',
  phone: '',
});

const form = reactive(blank());
const editingId = ref(null);
const isEditing = computed(() => editingId.value !== null);

const startEdit = (community) => {
  editingId.value = community.id;
  Object.assign(form, blank(), {
    designation: community.designation ?? '',
    headquarters_number: community.headquarters_number ?? '',
    headquarters_avenue: community.headquarters_avenue ?? '',
    headquarters_district: community.headquarters_district ?? '',
    headquarters_city: community.headquarters_city ?? '',
    headquarters_province: community.headquarters_province ?? '',
    headquarters_country: community.headquarters_country ?? 'RDC',
    authorization_number: community.authorization_number ?? '',
    email: community.email ?? '',
    website: community.website ?? '',
    phone: community.phone ?? '',
  });
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelEdit = () => {
  editingId.value = null;
  Object.assign(form, blank());
};

const submit = () => {
  isEditing.value
    ? router.put(`/communautes/${editingId.value}`, form, { preserveScroll: true, onSuccess: cancelEdit })
    : router.post('/communautes', form, { preserveScroll: true });
};

const remove = (community) => {
  if (window.confirm(`Supprimer la communaute "${community.designation}" ?`)) {
    router.delete(`/communautes/${community.id}`, { preserveScroll: true, onSuccess: () => (community.id === editingId.value ? cancelEdit() : null) });
  }
};
</script>

<template>
  <AppLayout title="Communautes">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>{{ isEditing ? 'Modifier la communaute' : 'Nouvelle communaute' }}</h2>
        <div class="cty-grid">
          <TextInput v-model="form.designation" label="Designation" required />
          <TextInput v-model="form.authorization_number" label="Numero autorisation" required />
          <TextInput v-model="form.headquarters_number" label="Numero siege" />
          <TextInput v-model="form.headquarters_avenue" label="Avenue siege" />
          <TextInput v-model="form.headquarters_district" label="Quartier siege" />
          <TextInput v-model="form.headquarters_city" label="Ville siege" required />
          <TextInput v-model="form.headquarters_province" label="Province" required />
          <TextInput v-model="form.headquarters_country" label="Pays" required />
          <TextInput v-model="form.email" label="Email" type="email" />
          <TextInput v-model="form.website" label="Site web" type="url" />
          <TextInput v-model="form.phone" label="Telephone" />
        </div>
        <div class="cty-actions">
          <button class="btn">{{ isEditing ? 'Enregistrer' : 'Enregistrer' }}</button>
          <button v-if="isEditing" class="btn secondary" type="button" @click="cancelEdit">Annuler</button>
        </div>
      </form>

      <section class="panel">
        <h2>Registre <small>{{ communities.total }} au total</small></h2>
        <div class="cty-table-wrap">
          <table class="cty-table">
            <thead>
              <tr><th>Designation</th><th>Autorisation</th><th>Siege</th><th>Eglises</th><th>Telephone</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="community in communities.data" :key="community.id" :class="{ 'is-editing': community.id === editingId }">
                <td><strong>{{ community.designation }}</strong></td>
                <td>{{ community.authorization_number }}</td>
                <td>{{ community.headquarters_city }}, {{ community.headquarters_province }}</td>
                <td>{{ community.churches_count }}</td>
                <td>{{ community.phone || '—' }}</td>
                <td>
                  <div class="row-actions">
                    <button class="icon-action is-blue" type="button" title="Modifier" @click="startEdit(community)"><i class="bi bi-pencil-square" /></button>
                    <button class="icon-action is-red" type="button" title="Supprimer" @click="remove(community)"><i class="bi bi-trash" /></button>
                  </div>
                </td>
              </tr>
              <tr v-if="!communities.data.length"><td colspan="6">Aucune communaute.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="communities" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.cty-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.cty-actions { display: flex; gap: 10px; }

.cty-table-wrap { overflow-x: auto; }
.cty-table { width: 100%; border-collapse: collapse; }
.cty-table th,
.cty-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
}
.cty-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; white-space: nowrap; }
.cty-table tr:last-child td { border-bottom: 0; }
.cty-table tr.is-editing { background: var(--blue-soft); }

.btn.sm { min-height: 30px; padding: 0 10px; font-size: 12px; }
</style>
