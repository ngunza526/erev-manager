<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

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
const destroy = (account) => router.delete(`/plan-comptable/${account.id}`, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Plan comptable">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau compte</h2>
        <div class="row"><TextInput v-model="form.code" label="Code" required /><TextInput v-model="form.class" label="Classe" type="number" required /></div>
        <TextInput v-model="form.label" label="Libelle" required />
        <label>Sens normal<select v-model="form.normal_side"><option value="debit">Debit</option><option value="credit">Credit</option></select></label>
        <button class="btn">Creer le compte</button>
      </form>
      <section class="panel">
        <h2>Cadre SYCEBNL</h2>
        <div class="list">
          <article v-for="account in accounts.data" :key="account.id" class="item">
            <header><strong>{{ account.code }} · {{ account.label }}</strong><small>Classe {{ account.class }}</small></header>
            <div class="tags">
              <span class="tag">{{ account.normal_side }}</span>
              <span class="tag gold">{{ account.is_active ? 'actif' : 'inactif' }}</span>
              <button class="btn secondary" type="button" @click="toggle(account)">{{ account.is_active ? 'Desactiver' : 'Activer' }}</button>
              <button v-if="!account.is_system" class="btn secondary" type="button" @click="destroy(account)">Supprimer</button>
            </div>
          </article>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
