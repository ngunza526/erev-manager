<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
  logs: Object,
  filters: Object,
  actions: Array,
  churches: Array,
});

const form = reactive({
  action: props.filters?.action ?? '',
  church_id: props.filters?.church_id ?? '',
  from: props.filters?.from ?? '',
  to: props.filters?.to ?? '',
});

const query = () => Object.fromEntries(Object.entries(form).filter(([, value]) => value !== '' && value !== null));

const applyFilters = () => router.get('/journal-audit', query(), { preserveScroll: true, preserveState: true });
const resetFilters = () => {
  form.action = '';
  form.church_id = '';
  form.from = '';
  form.to = '';
  router.get('/journal-audit', {}, { preserveScroll: true });
};
const goTo = (url) => url && router.get(url, {}, { preserveScroll: true, preserveState: true });

const formatDate = (value) => (value ? new Date(value).toLocaleString('fr-FR') : '');
const contextEntries = (context) => (context ? Object.entries(context) : []);
const renderValue = (value) => (Array.isArray(value) ? (value.length ? value.join(', ') : '—') : String(value ?? '—'));
</script>

<template>
  <AppLayout title="Journal d'audit">
    <div class="grid">
      <form class="panel form" @submit.prevent="applyFilters">
        <h2>Filtres</h2>
        <div class="grid two">
          <label>
            Action
            <select v-model="form.action">
              <option value="">Toutes</option>
              <option v-for="action in actions" :key="action" :value="action">{{ action }}</option>
            </select>
          </label>
          <label v-if="churches.length > 1">
            Eglise
            <select v-model="form.church_id">
              <option value="">Toutes</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <label>
            Du
            <input v-model="form.from" type="date" />
          </label>
          <label>
            Au
            <input v-model="form.to" type="date" />
          </label>
        </div>
        <div class="tags">
          <button class="btn" type="submit">Filtrer</button>
          <button class="btn secondary" type="button" @click="resetFilters">Reinitialiser</button>
        </div>
      </form>

      <section class="panel">
        <h2>Evenements <small>{{ logs.total }} au total</small></h2>
        <div class="list">
          <article v-for="log in logs.data" :key="log.id" class="item">
            <header>
              <strong>{{ log.action }}</strong>
              <small>{{ formatDate(log.created_at) }}</small>
            </header>
            <small>
              {{ log.actor_label || 'systeme' }}
              <template v-if="log.church"> — {{ log.church.designation }}</template>
              <template v-else-if="log.community"> — {{ log.community.designation }} (coordination)</template>
              <template v-if="log.ip_address"> — {{ log.ip_address }}</template>
            </small>
            <small v-if="log.auditable_type">
              Cible : {{ log.auditable_type }}#{{ log.auditable_id }}
            </small>
            <div v-if="contextEntries(log.context).length" class="tags">
              <span v-for="[key, value] in contextEntries(log.context)" :key="key" class="tag">
                {{ key }}: {{ renderValue(value) }}
              </span>
            </div>
          </article>
          <p v-if="!logs.data.length" class="item">Aucun evenement pour ces criteres.</p>
        </div>

        <div v-if="logs.last_page > 1" class="tags">
          <button class="btn secondary" type="button" :disabled="!logs.prev_page_url" @click="goTo(logs.prev_page_url)">Precedent</button>
          <span>Page {{ logs.current_page }} / {{ logs.last_page }}</span>
          <button class="btn secondary" type="button" :disabled="!logs.next_page_url" @click="goTo(logs.next_page_url)">Suivant</button>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
