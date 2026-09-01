<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
defineProps({ churches: Array, items: Object });
const form = reactive({ church_id: '', full_name: '', phone: '', email: '', visit_source: 'culte', visited_at: new Date().toISOString().slice(0, 10), follow_up_status: 'a_relancer', notes: '' });
const submit = () => router.post('/visiteurs', form, { preserveScroll: true });
</script>
<template>
  <AppLayout title="Visiteurs">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau visiteur</h2>
        <label>Eglise<select v-model="form.church_id" required><option value="">Choisir</option><option v-for="c in churches" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <TextInput v-model="form.full_name" label="Nom complet" required />
        <div class="row"><TextInput v-model="form.phone" label="Telephone" /><TextInput v-model="form.email" label="Email" type="email" /></div>
        <div class="row"><TextInput v-model="form.visit_source" label="Source visite" required /><TextInput v-model="form.visited_at" label="Date visite" type="date" required /></div>
        <TextInput v-model="form.follow_up_status" label="Statut relance" required />
        <TextInput v-model="form.notes" label="Notes" />
        <button class="btn">Enregistrer</button>
      </form>
      <section class="panel"><h2>Suivi visiteurs</h2><div class="list"><article v-for="item in items.data" :key="item.id" class="item"><header><strong>{{ item.full_name }}</strong><small>{{ item.follow_up_status }}</small></header><small>{{ item.church?.designation }} · {{ item.phone }} · {{ item.visited_at }}</small><div class="tags"><span class="tag">{{ item.visit_source }}</span></div></article></div></section>
    </div>
  </AppLayout>
</template>
