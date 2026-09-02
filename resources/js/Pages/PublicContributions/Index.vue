<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({ pending: Array, recent: Array });

const notes = reactive({});

const approve = (item) => router.post(`/contributions-publiques/${item.id}/valider`, {}, { preserveScroll: true });
const reject = (item) => router.post(`/contributions-publiques/${item.id}/rejeter`, { note: notes[item.id] || '' }, { preserveScroll: true });

const money = (item) => `${Number(item.amount).toLocaleString('fr-FR')} ${item.currency}`;
const kindLabel = (kind) => (kind === 'event_registration' ? 'Inscription evenement' : 'Don');
const statusLabel = (s) => ({ validated: 'Validee', rejected: 'Rejetee', pending: 'En attente' }[s] || s);
const formatDate = (value) => (value ? new Date(value).toLocaleString('fr-FR') : '');
</script>

<template>
  <AppLayout title="Contributions publiques">
    <div class="grid">
      <section class="panel">
        <h2>A valider <small>{{ pending.length }}</small></h2>
        <p v-if="!pending.length" class="item">Aucune contribution en attente.</p>
        <div class="list">
          <article v-for="item in pending" :key="item.id" class="item">
            <header>
              <strong>{{ money(item) }}</strong>
              <small>{{ kindLabel(item.kind) }} — {{ formatDate(item.created_at) }}</small>
            </header>
            <small>
              {{ item.contributor_name || 'anonyme' }}
              <template v-if="item.contribution_type"> — {{ item.contribution_type }}</template>
              <template v-if="item.event"> — {{ item.event.title }}</template>
              — {{ item.payment_method }}
              <template v-if="item.church"> — {{ item.church.designation }}</template>
            </small>
            <input v-model="notes[item.id]" placeholder="Motif (si rejet)" />
            <div class="tags">
              <button class="btn" type="button" @click="approve(item)">Valider</button>
              <button class="btn secondary" type="button" @click="reject(item)">Rejeter</button>
            </div>
          </article>
        </div>
      </section>

      <section class="panel">
        <h2>Historique recent</h2>
        <div class="list">
          <article v-for="item in recent" :key="item.id" class="item">
            <header>
              <strong>{{ money(item) }}</strong>
              <small>{{ statusLabel(item.status) }} — {{ formatDate(item.reviewed_at) }}</small>
            </header>
            <small>
              {{ kindLabel(item.kind) }} — {{ item.contributor_name || 'anonyme' }}
              <template v-if="item.reviewer"> — par {{ item.reviewer.name }}</template>
              <template v-if="item.review_note"> — « {{ item.review_note }} »</template>
            </small>
          </article>
          <p v-if="!recent.length" class="item">Rien pour le moment.</p>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
