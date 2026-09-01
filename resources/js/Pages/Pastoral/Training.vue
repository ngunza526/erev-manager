<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
defineProps({ churches: Array, items: Object });
const form = reactive({ church_id: '', title: '', category: 'formation', instructor_name: '', starts_at: '', ends_at: '', enrollments_count: 0, certificate_enabled: true });
const submit = () => router.post('/formations', form, { preserveScroll: true });
</script>
<template>
  <AppLayout title="Formations">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau cours</h2>
        <label>Eglise<select v-model="form.church_id" required><option value="">Choisir</option><option v-for="c in churches" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <TextInput v-model="form.title" label="Titre" required />
        <div class="row"><TextInput v-model="form.category" label="Categorie" required /><TextInput v-model="form.instructor_name" label="Formateur" required /></div>
        <div class="row"><TextInput v-model="form.starts_at" label="Debut" type="date" required /><TextInput v-model="form.ends_at" label="Fin" type="date" /></div>
        <TextInput v-model="form.enrollments_count" label="Inscriptions" type="number" />
        <label><input v-model="form.certificate_enabled" type="checkbox" /> Certificat actif</label>
        <button class="btn">Creer</button>
      </form>
      <section class="panel"><h2>Cours</h2><div class="list"><article v-for="item in items.data" :key="item.id" class="item"><header><strong>{{ item.title }}</strong><small>{{ item.category }}</small></header><small>{{ item.church?.designation }} · {{ item.instructor_name }} · {{ item.starts_at }}</small><div class="tags"><span class="tag">{{ item.enrollments_count }} inscrit(s)</span><span class="tag gold">{{ item.certificate_enabled ? 'certificat' : 'sans certificat' }}</span></div></article></div></section>
    </div>
  </AppLayout>
</template>
