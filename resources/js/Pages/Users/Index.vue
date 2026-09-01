<script setup>
import { reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({
  users: Object,
  members: Array,
  churches: Array,
  communities: Array,
  roles: Object,
  workspace: String,
});

const form = reactive({
  name: '',
  member_id: '',
  church_id: '',
  community_id: props.communities?.[0]?.id ?? '',
  email: '',
  password: '',
  level: 'eglise',
  role: 'SuperAdmin',
});

const availableRoles = computed(() => props.roles[form.level] ?? []);
watch(() => form.level, () => {
  form.role = availableRoles.value[0] ?? '';
  if (form.level === 'coordination') {
    form.church_id = '';
  }
});

const submit = () => router.post('/utilisateurs', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Utilisateurs">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvel utilisateur</h2>
        <label>
          Espace
          <select v-model="form.level">
            <option value="coordination">Communaute</option>
            <option value="eglise">Eglise</option>
          </select>
        </label>
        <TextInput v-model="form.name" label="Nom complet" />
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
        <label>
          Membre effectif optionnel
          <select v-model="form.member_id">
            <option value="">Aucun</option>
            <option v-for="member in members" :key="member.id" :value="member.id">
              {{ member.last_name }} {{ member.middle_name }} {{ member.first_name }} - {{ member.church?.designation }}
            </option>
          </select>
        </label>
        <TextInput v-model="form.email" label="Login email" type="email" required />
        <TextInput v-model="form.password" label="Mot de passe" type="password" required />
        <label>
          Role
          <select v-model="form.role" required>
            <option v-for="role in availableRoles" :key="role">{{ role }}</option>
          </select>
        </label>
        <button class="btn">Creer</button>
      </form>
      <section class="panel">
        <h2>Comptes</h2>
        <div class="list">
          <article v-for="user in users.data" :key="user.id" class="item">
            <header><strong>{{ user.email }}</strong><small>{{ user.status }}</small></header>
            <small>{{ user.name }} - {{ user.level === 'coordination' ? 'communaute' : 'eglise' }} - OTP requis</small>
            <div class="tags">
              <span class="tag">{{ user.church?.designation ?? user.community?.designation ?? 'communaute' }}</span>
            </div>
          </article>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
