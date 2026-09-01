<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
defineProps({ churches: Array, items: Object });
const form = reactive({ church_id: '', volunteer_name: '', team: 'accueil', role: '', service_date: new Date().toISOString().slice(0, 10), availability_status: 'confirmed', notes: '' });
const submit = () => router.post('/volontaires', form, { preserveScroll: true });
</script>
<template>
  <AppLayout title="Volontariat">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Affectation volontaire</h2>
        <label>Eglise<select v-model="form.church_id" required><option value="">Choisir</option><option v-for="c in churches" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <TextInput v-model="form.volunteer_name" label="Volontaire" required />
        <div class="row"><TextInput v-model="form.team" label="Equipe" required /><TextInput v-model="form.role" label="Role" required /></div>
        <div class="row"><TextInput v-model="form.service_date" label="Date service" type="date" required /><TextInput v-model="form.availability_status" label="Disponibilite" required /></div>
        <TextInput v-model="form.notes" label="Notes" />
        <button class="btn">Planifier</button>
      </form>
      <section class="panel"><h2>Planning volontaires</h2><div class="list"><article v-for="item in items.data" :key="item.id" class="item"><header><strong>{{ item.volunteer_name }}</strong><small>{{ item.availability_status }}</small></header><small>{{ item.church?.designation }} · {{ item.service_date }}</small><div class="tags"><span class="tag">{{ item.team }}</span><span class="tag gold">{{ item.role }}</span></div></article></div></section>
    </div>
  </AppLayout>
</template>
