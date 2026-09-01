<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
defineProps({ churches: Array, items: Object });
const form = reactive({ church_id: '', full_name: '', conversion_date: new Date().toISOString().slice(0, 10), discipleship_stage: 'accueil', mentor_name: '', baptism_target_date: '', status: 'en_suivi', notes: '' });
const submit = () => router.post('/convertis', form, { preserveScroll: true });
</script>
<template>
  <AppLayout title="Nouveaux convertis">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau converti</h2>
        <label>Eglise<select v-model="form.church_id" required><option value="">Choisir</option><option v-for="c in churches" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <TextInput v-model="form.full_name" label="Nom complet" required />
        <div class="row"><TextInput v-model="form.conversion_date" label="Date conversion" type="date" required /><TextInput v-model="form.baptism_target_date" label="Bapteme prevu" type="date" /></div>
        <div class="row"><TextInput v-model="form.discipleship_stage" label="Etape discipleship" required /><TextInput v-model="form.mentor_name" label="Mentor" /></div>
        <TextInput v-model="form.status" label="Statut" required />
        <button class="btn">Enregistrer</button>
      </form>
      <section class="panel"><h2>Parcours</h2><div class="list"><article v-for="item in items.data" :key="item.id" class="item"><header><strong>{{ item.full_name }}</strong><small>{{ item.status }}</small></header><small>{{ item.church?.designation }} · {{ item.conversion_date }}</small><div class="tags"><span class="tag">{{ item.discipleship_stage }}</span><span class="tag gold">{{ item.mentor_name || 'mentor a definir' }}</span></div></article></div></section>
    </div>
  </AppLayout>
</template>
