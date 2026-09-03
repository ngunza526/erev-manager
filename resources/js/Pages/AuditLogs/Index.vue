<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';

const props = defineProps({
  logs: Object,
  filters: Object,
  actions: Array,
  churches: Array,
  perPage: Number,
  perPageOptions: Array,
});

const form = reactive({
  action: props.filters?.action ?? '',
  church_id: props.filters?.church_id ?? '',
  from: props.filters?.from ?? '',
  to: props.filters?.to ?? '',
  per_page: props.perPage ?? 25,
});

const query = () => Object.fromEntries(Object.entries(form).filter(([, value]) => value !== '' && value !== null));

const applyFilters = () => router.get('/journal-audit', query(), { preserveScroll: true, preserveState: true });
const resetFilters = () => {
  form.action = '';
  form.church_id = '';
  form.from = '';
  form.to = '';
  form.per_page = props.perPage ?? 25;
  router.get('/journal-audit', { per_page: form.per_page }, { preserveScroll: true });
};

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
        <div class="al-head">
          <h2>Evenements <small>{{ logs.total }} au total</small></h2>
          <label class="al-perpage">
            Par page
            <select v-model.number="form.per_page" @change="applyFilters">
              <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
            </select>
          </label>
        </div>
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

        <Pagination :meta="logs" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.al-head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.al-head h2 { margin: 0; }

.al-perpage {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 850;
  color: #475467;
}

.al-perpage select { width: auto; min-width: 72px; }
</style>
