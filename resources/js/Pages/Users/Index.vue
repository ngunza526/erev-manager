<script setup>
import { reactive, computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

const props = defineProps({
  users: Object,
  members: Array,
  churches: Array,
  communities: Array,
  roles: Object,
  workspace: String,
});

const blank = () => ({
  name: '',
  member_id: '',
  church_id: '',
  community_id: props.communities?.[0]?.id ?? '',
  email: '',
  password: '',
  level: 'eglise',
  role: '',
  status: 'actif',
});

const form = reactive(blank());
const editingId = ref(null);
const isEditing = computed(() => editingId.value !== null);
const availableRoles = computed(() => props.roles[form.level] ?? []);

watch(() => form.level, () => {
  if (!availableRoles.value.includes(form.role)) {
    form.role = availableRoles.value[0] ?? '';
  }
  if (form.level === 'coordination') {
    form.church_id = '';
  }
});

const startEdit = (user) => {
  editingId.value = user.id;
  Object.assign(form, blank(), {
    name: user.name ?? '',
    email: user.email ?? '',
    level: user.level ?? 'eglise',
    role: (user.roles || [])[0]?.name ?? '',
    status: user.status ?? 'actif',
    church_id: user.church_id ?? '',
    community_id: user.community_id ?? '',
  });
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelEdit = () => {
  editingId.value = null;
  Object.assign(form, blank());
};

const submit = () => {
  if (isEditing.value) {
    router.put(`/utilisateurs/${editingId.value}`, {
      name: form.name,
      email: form.email,
      level: form.level,
      role: form.role,
      status: form.status,
      church_id: form.church_id || null,
      community_id: form.community_id || null,
    }, { preserveScroll: true, onSuccess: cancelEdit });
    return;
  }
  router.post('/utilisateurs', form, { preserveScroll: true });
};

const spaceLabel = (level) => (level === 'coordination' ? 'Communaute' : 'Eglise');
const roleNames = (user) => (user.roles || []).map((role) => role.name).join(', ') || '—';
const scopeLabel = (user) => user.church?.designation ?? user.community?.designation ?? 'communaute';

const toggleStatus = (user) => router.patch(`/utilisateurs/${user.id}/statut`, {}, { preserveScroll: true });
const remove = (user) => {
  if (window.confirm(`Supprimer le compte "${user.email}" ?`)) {
    router.delete(`/utilisateurs/${user.id}`, { preserveScroll: true, onSuccess: () => (user.id === editingId.value ? cancelEdit() : null) });
  }
};
</script>

<template>
  <AppLayout title="Utilisateurs">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>{{ isEditing ? 'Modifier le compte' : 'Nouvel utilisateur' }}</h2>
        <div class="usr-grid">
          <label>
            Espace
            <select v-model="form.level">
              <option value="coordination">Communaute</option>
              <option value="eglise">Eglise</option>
            </select>
          </label>
          <TextInput v-model="form.name" label="Nom complet" :required="isEditing" />
          <label v-if="form.level === 'coordination'">
            Communaute
            <select v-model="form.community_id" required>
              <option value="">Choisir</option>
              <option v-for="community in communities" :key="community.id" :value="community.id">{{ community.designation }}</option>
            </select>
          </label>
          <label v-if="form.level === 'eglise'">
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <label v-if="!isEditing">
            Membre effectif optionnel
            <select v-model="form.member_id">
              <option value="">Aucun</option>
              <option v-for="member in members" :key="member.id" :value="member.id">
                {{ member.last_name }} {{ member.middle_name }} {{ member.first_name }} - {{ member.church?.designation }}
              </option>
            </select>
          </label>
          <TextInput v-model="form.email" label="Login email" type="email" required />
          <TextInput v-if="!isEditing" v-model="form.password" label="Mot de passe" type="password" required />
          <label>
            Role
            <select v-model="form.role" required>
              <option v-for="role in availableRoles" :key="role">{{ role }}</option>
            </select>
          </label>
          <label v-if="isEditing">
            Statut
            <select v-model="form.status">
              <option value="actif">actif</option>
              <option value="suspendu">suspendu</option>
            </select>
          </label>
        </div>
        <div class="usr-actions">
          <button class="btn">{{ isEditing ? 'Enregistrer' : 'Creer' }}</button>
          <button v-if="isEditing" class="btn secondary" type="button" @click="cancelEdit">Annuler</button>
        </div>
      </form>

      <section class="panel">
        <h2>Comptes <small>{{ users.total }} au total</small></h2>
        <div class="usr-table-wrap">
          <table class="usr-table">
            <thead>
              <tr><th>Email</th><th>Nom</th><th>Espace</th><th>Role</th><th>Perimetre</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="user in users.data" :key="user.id" :class="{ 'is-editing': user.id === editingId }">
                <td><strong>{{ user.email }}</strong></td>
                <td>{{ user.name }}</td>
                <td>{{ spaceLabel(user.level) }}</td>
                <td>{{ roleNames(user) }}</td>
                <td>{{ scopeLabel(user) }}</td>
                <td><span class="tag" :class="{ gold: user.status !== 'actif' }">{{ user.status }}</span></td>
                <td>
                  <div class="row-actions">
                    <button
                      class="icon-action is-green"
                      :class="{ 'is-on': user.status === 'actif' }"
                      type="button"
                      :title="user.status === 'actif' ? 'Suspendre' : 'Reactiver'"
                      @click="toggleStatus(user)"
                    >
                      <i :class="user.status === 'actif' ? 'bi bi-toggle-on' : 'bi bi-toggle-off'" />
                    </button>
                    <button class="icon-action is-blue" type="button" title="Modifier" @click="startEdit(user)"><i class="bi bi-pencil-square" /></button>
                    <button class="icon-action is-red" type="button" title="Supprimer" @click="remove(user)"><i class="bi bi-trash" /></button>
                  </div>
                </td>
              </tr>
              <tr v-if="!users.data.length"><td colspan="7">Aucun compte.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="users" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.usr-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.usr-actions { display: flex; gap: 10px; }

.usr-table-wrap { overflow-x: auto; }
.usr-table { width: 100%; border-collapse: collapse; }
.usr-table th,
.usr-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  white-space: nowrap;
}
.usr-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; }
.usr-table tr:last-child td { border-bottom: 0; }
.usr-table tr.is-editing { background: var(--blue-soft); }

.btn.sm { min-height: 30px; padding: 0 10px; font-size: 12px; }
</style>
