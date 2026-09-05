<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ entries: Object });
</script>

<template>
  <AppLayout title="Comptabilite — Journal">
    <section class="panel">
      <h2>Journal <small>{{ entries.total }} au total</small></h2>
      <div class="tags acct-reports">
        <Link class="btn secondary" href="/rapports/balance">Balance generale</Link>
        <Link class="btn secondary" href="/rapports/etats-financiers">Etats financiers</Link>
        <a class="btn secondary" href="/rapports/balance.pdf">Balance PDF</a>
        <a class="btn secondary" href="/rapports/balance.xlsx">Balance Excel</a>
        <a class="btn secondary" href="/rapports/etats-ohada.pdf">Etats OHADA PDF</a>
        <a class="btn secondary" href="/rapports/etats-ohada.xlsx">Etats OHADA Excel</a>
      </div>
      <div class="acct-table-wrap">
        <table class="acct-table">
          <thead>
            <tr><th>Reference</th><th>Description</th><th>Eglise</th><th>Devise</th><th>Statut</th><th>Lignes</th></tr>
          </thead>
          <tbody>
            <tr v-for="entry in entries.data" :key="entry.id">
              <td>{{ entry.reference }}</td>
              <td><strong>{{ entry.description }}</strong></td>
              <td>{{ entry.church?.designation ?? '—' }}</td>
              <td>{{ entry.currency }}</td>
              <td><span class="tag" :class="{ gold: entry.status !== 'validee' }">{{ entry.status }}</span></td>
              <td>
                <div class="acct-lines">
                  <span v-for="line in entry.lines" :key="line.id" class="tag">
                    {{ line.account?.code }} {{ line.label }} · D{{ line.debit }} / C{{ line.credit }}
                  </span>
                </div>
              </td>
            </tr>
            <tr v-if="!entries.data.length"><td colspan="6">Aucune ecriture.</td></tr>
          </tbody>
        </table>
      </div>
      <Pagination :meta="entries" />
    </section>
  </AppLayout>
</template>

<style scoped>
.acct-reports { margin-bottom: 12px; }

.acct-table-wrap { overflow-x: auto; }
.acct-table { width: 100%; border-collapse: collapse; }
.acct-table th,
.acct-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  vertical-align: middle;
}
.acct-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; white-space: nowrap; }
.acct-table tr:last-child td { border-bottom: 0; }
.acct-lines { display: flex; flex-wrap: wrap; gap: 6px; }
</style>
