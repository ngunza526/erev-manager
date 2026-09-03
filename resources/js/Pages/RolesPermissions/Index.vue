<script setup>
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({ roles: Array, permissions: Array });

// Ordre d'affichage impose des roles (les inconnus suivent, en alphabetique).
const ROLE_ORDER = ['Administrateur', 'AdminFin', 'Auditeur', 'Caissier', 'Secretaire', 'SuperAdmin plateforme'];

const roleForm = reactive({ name: '', level: 'eglise', permissions: [] });
const permissionForm = reactive({ name: '' });
const rolePermissions = reactive({});
props.roles.forEach((role) => { rolePermissions[role.id] = role.permissions.map((permission) => permission.name); });

const permissionNames = computed(() => props.permissions.map((permission) => permission.name));

const orderedRoles = computed(() => [...props.roles].sort((a, b) => {
  const rank = (name) => {
    const index = ROLE_ORDER.indexOf(name);
    return index === -1 ? ROLE_ORDER.length : index;
  };
  return rank(a.name) - rank(b.name) || a.name.localeCompare(b.name);
}));

const createRole = () => router.post('/roles-permissions/roles', roleForm, { preserveScroll: true });
const createPermission = () => router.post('/roles-permissions/permissions', permissionForm, { preserveScroll: true });
const syncRole = (role) => router.put(`/roles-permissions/roles/${role.id}/permissions`, { permissions: rolePermissions[role.id] || [] }, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Roles et permissions">
    <div class="rp">
      <!-- 1. Deux blocs cote a cote, sur toute la largeur -->
      <div class="rp-top">
        <form class="panel form" @submit.prevent="createRole">
          <h2>Roles et permissions</h2>
          <p class="muted">Creer un role et lui attribuer ses permissions.</p>
          <TextInput v-model="roleForm.name" label="Nom du role" required />
          <label>
            Niveau
            <select v-model="roleForm.level">
              <option value="eglise">Eglise</option>
              <option value="coordination">Coordination</option>
            </select>
          </label>
          <label>
            Permissions
            <select v-model="roleForm.permissions" multiple>
              <option v-for="permission in permissionNames" :key="permission" :value="permission">{{ permission }}</option>
            </select>
          </label>
          <button class="btn">Creer le role</button>
        </form>

        <form class="panel form" @submit.prevent="createPermission">
          <h2>Nouvelle permission</h2>
          <p class="muted">Ajouter une permission attribuable aux roles.</p>
          <TextInput v-model="permissionForm.name" label="Nom de la permission" required />
          <button class="btn">Creer la permission</button>
        </form>
      </div>

      <!-- 2. Accordeons pleine largeur, dans l'ordre demande -->
      <details class="rp-acc" open>
        <summary>
          <span>Roles existants</span>
          <span class="rp-count">{{ orderedRoles.length }}</span>
        </summary>
        <div class="rp-acc-body">
          <div class="rp-table-wrap">
            <table class="rp-table">
              <thead>
                <tr><th>Role</th><th>Niveau</th><th>Permissions</th></tr>
              </thead>
              <tbody>
                <tr v-for="(role, index) in orderedRoles" :key="role.id">
                  <td><strong>{{ index + 1 }}. {{ role.name }}</strong></td>
                  <td>{{ role.level }}</td>
                  <td>{{ (rolePermissions[role.id] || []).length }} / {{ permissionNames.length }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </details>

      <details v-for="(role, index) in orderedRoles" :key="role.id" class="rp-acc">
        <summary>
          <span>{{ index + 1 }}. {{ role.name }}</span>
          <span class="rp-count">{{ (rolePermissions[role.id] || []).length }} / {{ permissionNames.length }}</span>
        </summary>
        <div class="rp-acc-body">
          <small class="muted">Niveau : {{ role.level }}</small>
          <label>
            Permissions attribuees
            <select v-model="rolePermissions[role.id]" multiple size="12">
              <option v-for="permission in permissionNames" :key="permission" :value="permission">{{ permission }}</option>
            </select>
          </label>
          <div class="tags">
            <span v-for="permission in rolePermissions[role.id] || []" :key="permission" class="tag">{{ permission }}</span>
            <span v-if="!(rolePermissions[role.id] || []).length" class="muted">Aucune permission attribuee.</span>
          </div>
          <button class="btn secondary" type="button" @click="syncRole(role)">Mettre a jour</button>
        </div>
      </details>
    </div>
  </AppLayout>
</template>

<style scoped>
.rp { display: grid; gap: 16px; }

.rp-top {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  align-items: start;
}

@media (max-width: 860px) {
  .rp-top { grid-template-columns: 1fr; }
}

.rp-acc {
  width: 100%;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: var(--panel);
  box-shadow: 0 8px 24px rgba(18, 32, 54, .05);
  overflow: hidden;
}

.rp-acc > summary {
  list-style: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 18px;
  font-size: 16px;
  font-weight: 920;
}

.rp-acc > summary::-webkit-details-marker { display: none; }
.rp-acc > summary::after { content: "+"; color: var(--blue); font-size: 18px; font-weight: 950; }
.rp-acc[open] > summary::after { content: "\2212"; }
.rp-acc[open] > summary { border-bottom: 1px solid var(--line); }

.rp-count {
  min-height: 24px;
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0 10px;
  background: var(--blue-soft);
  color: var(--blue-dark);
  font-size: 12px;
  font-weight: 950;
}

.rp-acc-body { padding: 16px 18px; display: grid; gap: 12px; }

.rp-table-wrap { overflow-x: auto; }
.rp-table { width: 100%; border-collapse: collapse; }
.rp-table th,
.rp-table td { text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--line); font-size: 14px; }
.rp-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; }
.rp-table tr:last-child td { border-bottom: 0; }
</style>
