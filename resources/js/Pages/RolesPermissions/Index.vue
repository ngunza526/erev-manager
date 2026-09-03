<script setup>
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import MultiSelect from '../../Components/MultiSelect.vue';
import ClientPaginator from '../../Components/ClientPaginator.vue';

const props = defineProps({ roles: Array, permissions: Array });

// Ordre d'affichage impose des roles (les inconnus suivent, en alphabetique).
const ROLE_ORDER = ['Administrateur', 'AdminFin', 'Auditeur', 'Caissier', 'Secretaire', 'SuperAdmin plateforme'];

const roleForm = reactive({ name: '', level: 'eglise', permissions: [] });
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

// Table "Roles existants" enrichie du rang (pour l'affichage pagine).
const rolesRows = computed(() => orderedRoles.value.map((role, index) => ({ ...role, position: index + 1 })));

const createRole = () => router.post('/roles-permissions/roles', roleForm, { preserveScroll: true });
const syncRole = (role) => router.put(`/roles-permissions/roles/${role.id}/permissions`, { permissions: rolePermissions[role.id] || [] }, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Roles et permissions">
    <div class="rp">
      <!-- Formulaire "Roles et permissions" sur toute la largeur.
           Les permissions ne sont plus creees ici : elles sont generees
           cote back-end (permissions:sync / seeder) a partir des routes. -->
      <form class="panel form" @submit.prevent="createRole">
        <h2>Roles et permissions</h2>
        <p class="muted">Creer un role et lui attribuer des permissions existantes.</p>
        <div class="rp-role-grid">
          <TextInput v-model="roleForm.name" label="Nom du role" required />
          <label>
            Niveau
            <select v-model="roleForm.level">
              <option value="eglise">Eglise</option>
              <option value="coordination">Coordination</option>
            </select>
          </label>
        </div>
        <MultiSelect
          v-model="roleForm.permissions"
          :options="permissionNames"
          label="Permissions"
          placeholder="Rechercher et ajouter des permissions..."
        />
        <button class="btn">Creer le role</button>
      </form>

      <!-- Accordeon "Roles existants" : liste paginee -->
      <details class="rp-acc" open>
        <summary>
          <span>Roles existants</span>
          <span class="rp-count">{{ rolesRows.length }}</span>
        </summary>
        <div class="rp-acc-body">
          <ClientPaginator :items="rolesRows" :per-page="10">
            <template #default="{ items }">
              <div class="rp-table-wrap">
                <table class="rp-table">
                  <thead>
                    <tr><th>Role</th><th>Niveau</th><th>Permissions</th></tr>
                  </thead>
                  <tbody>
                    <tr v-for="role in items" :key="role.id">
                      <td><strong>{{ role.position }}. {{ role.name }}</strong></td>
                      <td>{{ role.level }}</td>
                      <td>{{ (rolePermissions[role.id] || []).length }} / {{ permissionNames.length }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>
          </ClientPaginator>
        </div>
      </details>

      <!-- Un accordeon par role : liste paginee des permissions (cochees = attribuees) -->
      <details v-for="role in rolesRows" :key="role.id" class="rp-acc">
        <summary>
          <span>{{ role.position }}. {{ role.name }}</span>
          <span class="rp-count">{{ (rolePermissions[role.id] || []).length }} / {{ permissionNames.length }}</span>
        </summary>
        <div class="rp-acc-body">
          <small class="muted">Niveau : {{ role.level }}</small>
          <ClientPaginator :items="permissionNames" :per-page="10">
            <template #default="{ items }">
              <div class="rp-perms">
                <label v-for="permission in items" :key="permission" class="rp-perm">
                  <input type="checkbox" :value="permission" v-model="rolePermissions[role.id]" />
                  <span>{{ permission }}</span>
                </label>
              </div>
            </template>
          </ClientPaginator>
          <button class="btn secondary" type="button" @click="syncRole(role)">Mettre a jour</button>
        </div>
      </details>
    </div>
  </AppLayout>
</template>

<style scoped>
.rp { display: grid; gap: 16px; }

.rp-role-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

@media (max-width: 640px) {
  .rp-role-grid { grid-template-columns: 1fr; }
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

.rp-perms {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 6px 16px;
}

.rp-perm {
  display: flex;
  align-items: center;
  gap: 8px;
  min-height: 34px;
  font-size: 14px;
  font-weight: 700;
  color: var(--ink);
}

.rp-perm input { width: 16px; min-height: 16px; }
</style>
