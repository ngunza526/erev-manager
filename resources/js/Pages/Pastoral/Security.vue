<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
defineProps({ churches: Array, items: Object });
const form = reactive({ church_id: '', title: '', incident_type: 'general', severity: 'medium', occurred_at: '', reported_by: '', status: 'open', description: '' });
const submit = () => router.post('/incidents', form, { preserveScroll: true });
</script>
<template>
  <AppLayout title="Securite et incidents">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvel incident</h2>
        <label>Eglise<select v-model="form.church_id" required><option value="">Choisir</option><option v-for="c in churches" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <TextInput v-model="form.title" label="Titre" required />
        <div class="row"><TextInput v-model="form.incident_type" label="Type" required /><TextInput v-model="form.severity" label="Gravite" required /></div>
        <div class="row"><TextInput v-model="form.occurred_at" label="Date et heure" type="datetime-local" required /><TextInput v-model="form.reported_by" label="Rapporte par" required /></div>
        <TextInput v-model="form.status" label="Statut" required />
        <label>Description<input v-model="form.description" required /></label>
        <button class="btn">Enregistrer</button>
      </form>
      <section class="panel"><h2>Journal securite</h2><div class="list"><article v-for="item in items.data" :key="item.id" class="item"><header><strong>{{ item.title }}</strong><small>{{ item.status }}</small></header><small>{{ item.church?.designation }} · {{ item.occurred_at }} · {{ item.reported_by }}</small><div class="tags"><span class="tag">{{ item.incident_type }}</span><span class="tag gold">{{ item.severity }}</span></div></article></div></section>
    </div>
  </AppLayout>
</template>
