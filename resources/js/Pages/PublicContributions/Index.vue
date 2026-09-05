<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';

const props = defineProps({ pending: Object, recent: Object });

const notes = reactive({});

const approve = (item) => router.post(`/contributions-publiques/${item.id}/valider`, {}, { preserveScroll: true });
const reject = (item) => router.post(`/contributions-publiques/${item.id}/rejeter`, { note: notes[item.id] || '' }, { preserveScroll: true });

const money = (item) => `${Number(item.amount).toLocaleString('fr-FR')} ${item.currency}`;
const kindLabel = (kind) => (kind === 'event_registration' ? 'Inscription evenement' : 'Don');
const statusLabel = (s) => ({ validated: 'Validee', rejected: 'Rejetee', pending: 'En attente' }[s] || s);
const originLabel = (item) => item.event?.title || item.church?.designation || '—';
const formatDate = (value) => (value ? new Date(value).toLocaleString('fr-FR') : '—');
</script>

<template>
  <AppLayout title="Contributions publiques">
    <div class="grid">
      <section class="panel">
        <h2>A valider <small>{{ pending.total }} au total</small></h2>
        <div class="pc-table-wrap">
          <table class="pc-table">
            <thead>
              <tr><th>Montant</th><th>Type</th><th>Contributeur</th><th>Origine</th><th>Paiement</th><th>Recue le</th><th>Motif si rejet</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in pending.data" :key="item.id">
                <td><strong>{{ money(item) }}</strong></td>
                <td>{{ kindLabel(item.kind) }}<template v-if="item.contribution_type"> ({{ item.contribution_type }})</template></td>
                <td>{{ item.contributor_name || 'anonyme' }}</td>
                <td>{{ originLabel(item) }}</td>
                <td>{{ item.payment_method }}</td>
                <td>{{ formatDate(item.created_at) }}</td>
                <td><input v-model="notes[item.id]" class="pc-note" placeholder="Motif (si rejet)" /></td>
                <td>
                  <div class="row-actions">
                    <button class="icon-action is-green" type="button" title="Valider" @click="approve(item)"><i class="bi bi-check-circle" /></button>
                    <button class="icon-action is-red" type="button" title="Rejeter" @click="reject(item)"><i class="bi bi-x-circle" /></button>
                  </div>
                </td>
              </tr>
              <tr v-if="!pending.data.length"><td colspan="8">Aucune contribution en attente.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="pending" />
      </section>

      <section class="panel">
        <h2>Historique recent <small>{{ recent.total }} au total</small></h2>
        <div class="pc-table-wrap">
          <table class="pc-table">
            <thead>
              <tr><th>Montant</th><th>Type</th><th>Contributeur</th><th>Statut</th><th>Traite le</th><th>Par</th><th>Motif</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in recent.data" :key="item.id">
                <td><strong>{{ money(item) }}</strong></td>
                <td>{{ kindLabel(item.kind) }}</td>
                <td>{{ item.contributor_name || 'anonyme' }}</td>
                <td><span class="tag" :class="{ gold: item.status !== 'validated' }">{{ statusLabel(item.status) }}</span></td>
                <td>{{ formatDate(item.reviewed_at) }}</td>
                <td>{{ item.reviewer?.name ?? '—' }}</td>
                <td>{{ item.review_note || '—' }}</td>
              </tr>
              <tr v-if="!recent.data.length"><td colspan="7">Rien pour le moment.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="recent" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.pc-table-wrap { overflow-x: auto; }
.pc-table { width: 100%; border-collapse: collapse; }
.pc-table th,
.pc-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  vertical-align: middle;
}
.pc-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; white-space: nowrap; }
.pc-table tr:last-child td { border-bottom: 0; }
.pc-note { min-width: 160px; }
</style>
