<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ accounts: Object });

const form = reactive({ code: '', label: '', class: 1, normal_side: 'debit', is_active: true });
const submit = () => router.post('/plan-comptable', form, { preserveScroll: true });
const toggle = (account) => router.put(`/plan-comptable/${account.id}`, {
  code: account.code,
  label: account.label,
  class: account.class,
  normal_side: account.normal_side,
  is_active: !account.is_active,
}, { preserveScroll: true });
const destroy = (account) => {
  if (window.confirm(`Supprimer le compte "${account.code} - ${account.label}" ?`)) {
    router.delete(`/plan-comptable/${account.id}`, { preserveScroll: true });
  }
};
</script>

<template>
  <AppLayout title="Plan comptable">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau compte</h2>
        <div class="coa-grid">
          <TextInput v-model="form.code" label="Code" required />
          <TextInput v-model="form.class" label="Classe" type="number" required />
          <TextInput v-model="form.label" label="Libelle" required />
          <label>
            Sens normal
            <select v-model="form.normal_side"><option value="debit">Debit</option><option value="credit">Credit</option></select>
          </label>
        </div>
        <button class="btn">Creer le compte</button>
      </form>

      <section class="panel">
        <h2>Cadre SYCEBNL <small>{{ accounts.total }} au total</small></h2>
        <div class="coa-table-wrap">
          <table class="coa-table">
            <thead>
              <tr><th>Code</th><th>Libelle</th><th>Classe</th><th>Sens</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="account in accounts.data" :key="account.id">
                <td>{{ account.code }}</td>
                <td><strong>{{ account.label }}</strong></td>
                <td>{{ account.class }}</td>
                <td>{{ account.normal_side }}</td>
                <td><span class="tag" :class="{ gold: !account.is_active }">{{ account.is_active ? 'actif' : 'inactif' }}</span></td>
                <td>
                  <div class="row-actions">
                    <button
                      class="icon-action is-green"
                      :class="{ 'is-on': account.is_active }"
                      type="button"
                      :title="account.is_active ? 'Desactiver' : 'Activer'"
                      @click="toggle(account)"
                    >
                      <i :class="account.is_active ? 'bi bi-toggle-on' : 'bi bi-toggle-off'" />
                    </button>
                    <button
                      v-if="!account.is_system"
                      class="icon-action is-red"
                      type="button"
                      title="Supprimer"
                      @click="destroy(account)"
                    >
                      <i class="bi bi-trash" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!accounts.data.length"><td colspan="6">Aucun compte.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="accounts" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.coa-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.coa-table-wrap { overflow-x: auto; }
.coa-table { width: 100%; border-collapse: collapse; }
.coa-table th,
.coa-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
}
.coa-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; white-space: nowrap; }
.coa-table tr:last-child td { border-bottom: 0; }
</style>
