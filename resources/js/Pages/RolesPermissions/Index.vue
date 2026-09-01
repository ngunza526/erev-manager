<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({ roles: Array, permissions: Array });
const roleForm = reactive({ name: '', level: 'eglise', permissions: [] });
const permissionForm = reactive({ name: '' });
const rolePermissions = reactive({});
const permissionNames = () => props.permissions.map((permission) => permission.name);
props.roles.forEach((role) => { rolePermissions[role.id] = role.permissions.map((permission) => permission.name); });
const createRole = () => router.post('/roles-permissions/roles', roleForm, { preserveScroll: true });
const createPermission = () => router.post('/roles-permissions/permissions', permissionForm, { preserveScroll: true });
const syncRole = (role) => router.put(`/roles-permissions/roles/${role.id}/permissions`, { permissions: rolePermissions[role.id] || [] }, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Roles et permissions">
    <div class="grid two">
      <div class="grid">
        <form class="panel form" @submit.prevent="createPermission">
          <h2>Nouvelle permission</h2>
          <TextInput v-model="permissionForm.name" label="Nom permission" required />
          <button class="btn">Creer permission</button>
        </form>

        <form class="panel form" @submit.prevent="createRole">
          <h2>Nouveau role</h2>
          <TextInput v-model="roleForm.name" label="Nom role" required />
          <label>Niveau<select v-model="roleForm.level"><option value="eglise">Eglise</option><option value="coordination">Coordination</option></select></label>
          <label>Permissions<select v-model="roleForm.permissions" multiple><option v-for="permission in permissionNames()" :key="permission" :value="permission">{{ permission }}</option></select></label>
          <button class="btn">Creer role</button>
        </form>
      </div>

      <section class="panel">
        <h2>Roles existants</h2>
        <div class="list">
          <article v-for="role in roles" :key="role.id" class="item">
            <header><strong>{{ role.name }}</strong><small>{{ role.level }}</small></header>
            <label>Permissions<select v-model="rolePermissions[role.id]" multiple><option v-for="permission in permissionNames()" :key="permission" :value="permission">{{ permission }}</option></select></label>
            <div class="tags">
              <span v-for="permission in role.permissions" :key="permission.id" class="tag">{{ permission.name }}</span>
            </div>
            <button class="btn secondary" type="button" @click="syncRole(role)">Mettre a jour</button>
          </article>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
