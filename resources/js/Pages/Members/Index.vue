<script setup>
import { reactive, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ members: Object, churches: Array, statuses: Array });

const blank = () => ({
  church_id: '',
  last_name: '',
  middle_name: '',
  first_name: '',
  sex: 'Masculin',
  birth_date: '',
  birth_place: '',
  profession: '',
  marital_status: 'Celibataire',
  spouse: '',
});

const form = reactive(blank());
const editingId = ref(null);
const isEditing = computed(() => editingId.value !== null);

const startEdit = (member) => {
  editingId.value = member.id;
  Object.assign(form, blank(), {
    church_id: member.church_id ?? '',
    last_name: member.last_name ?? '',
    middle_name: member.middle_name ?? '',
    first_name: member.first_name ?? '',
    sex: member.sex ?? 'Masculin',
    birth_date: (member.birth_date ?? '').slice(0, 10),
    birth_place: member.birth_place ?? '',
    profession: member.profession ?? '',
    marital_status: member.marital_status ?? 'Celibataire',
    spouse: member.spouse ?? '',
  });
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelEdit = () => {
  editingId.value = null;
  Object.assign(form, blank());
};

const submit = () => {
  isEditing.value
    ? router.put(`/membres/${editingId.value}`, form, { preserveScroll: true, onSuccess: cancelEdit })
    : router.post('/membres', form, { preserveScroll: true });
};

const promote = (member, status) => router.patch(`/membres/${member.id}/statut`, { status }, { preserveScroll: true });
const fullName = (member) => `${member.last_name} ${member.middle_name} ${member.first_name}`;

const remove = (member) => {
  if (window.confirm(`Supprimer le membre "${fullName(member)}" ?`)) {
    router.delete(`/membres/${member.id}`, { preserveScroll: true, onSuccess: () => (member.id === editingId.value ? cancelEdit() : null) });
  }
};
</script>

<template>
  <AppLayout title="Membres">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>{{ isEditing ? 'Modifier le membre' : 'Nouveau membre' }}</h2>
        <div class="mbr-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <TextInput v-model="form.last_name" label="Nom" required />
          <TextInput v-model="form.middle_name" label="Postnom" required />
          <TextInput v-model="form.first_name" label="Prenom" required />
          <label>
            Sexe
            <select v-model="form.sex"><option>Masculin</option><option>Feminin</option></select>
          </label>
          <TextInput v-model="form.birth_date" label="Date naissance" type="date" required />
          <TextInput v-model="form.birth_place" label="Lieu naissance" required />
          <TextInput v-model="form.profession" label="Profession" required />
          <label>
            Etat civil
            <select v-model="form.marital_status"><option>Celibataire</option><option>Marie(e)</option><option>Veuf/Veuve</option></select>
          </label>
          <TextInput v-model="form.spouse" label="Conjoint si marie(e)" />
        </div>
        <div class="mbr-actions">
          <button class="btn">{{ isEditing ? 'Enregistrer' : 'Creer comme sympathisant' }}</button>
          <button v-if="isEditing" class="btn secondary" type="button" @click="cancelEdit">Annuler</button>
        </div>
      </form>

      <section class="panel">
        <h2>Registre <small>{{ members.total }} au total</small></h2>
        <div class="mbr-table-wrap">
          <table class="mbr-table">
            <thead>
              <tr><th>Nom complet</th><th>Eglise</th><th>Profession</th><th>Statut</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <tr v-for="member in members.data" :key="member.id" :class="{ 'is-editing': member.id === editingId }">
                <td><strong>{{ fullName(member) }}</strong></td>
                <td>{{ member.church?.designation ?? '—' }}</td>
                <td>{{ member.profession }}</td>
                <td>
                  <select
                    class="mbr-status"
                    :value="member.status"
                    @change="promote(member, $event.target.value)"
                  >
                    <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                  </select>
                </td>
                <td>
                  <div class="row-actions">
                    <button class="icon-action is-blue" type="button" title="Modifier" @click="startEdit(member)"><i class="bi bi-pencil-square" /></button>
                    <button class="icon-action is-red" type="button" title="Supprimer" @click="remove(member)"><i class="bi bi-trash" /></button>
                  </div>
                </td>
              </tr>
              <tr v-if="!members.data.length"><td colspan="5">Aucun membre.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="members" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.mbr-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.mbr-actions { display: flex; gap: 10px; }

.mbr-table-wrap { overflow-x: auto; }
.mbr-table { width: 100%; border-collapse: collapse; }
.mbr-table th,
.mbr-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  vertical-align: middle;
}
.mbr-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; white-space: nowrap; }
.mbr-table tr:last-child td { border-bottom: 0; }
.mbr-table tr.is-editing { background: var(--blue-soft); }

.mbr-status { width: auto; min-width: 130px; min-height: 34px; }
</style>
