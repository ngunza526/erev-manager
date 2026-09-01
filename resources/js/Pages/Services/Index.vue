<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

defineProps({ churches: Array, services: Object });
const form = reactive({ church_id: '', title: '', service_type: 'culte', starts_at: '', ends_at: '', preacher: '', worship_leader: '', attendance_count: 0, notes: '' });
const submit = () => router.post('/services', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Services et cultes">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau service</h2>
        <label>Eglise<select v-model="form.church_id" required><option value="">Choisir</option><option v-for="c in churches" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <TextInput v-model="form.title" label="Titre" required />
        <div class="row"><TextInput v-model="form.service_type" label="Type" required /><TextInput v-model="form.attendance_count" label="Presence" type="number" /></div>
        <div class="row"><TextInput v-model="form.starts_at" label="Debut" type="datetime-local" required /><TextInput v-model="form.ends_at" label="Fin" type="datetime-local" /></div>
        <div class="row"><TextInput v-model="form.preacher" label="Predicateur" /><TextInput v-model="form.worship_leader" label="Conducteur louange" /></div>
        <TextInput v-model="form.notes" label="Notes" />
        <button class="btn">Creer</button>
      </form>
      <section class="panel">
        <h2>Planning</h2>
        <div class="list">
          <article v-for="service in services.data" :key="service.id" class="item">
            <header><strong>{{ service.title }}</strong><small>{{ service.service_type }}</small></header>
            <small>{{ service.church?.designation }} · {{ service.starts_at }} · {{ service.attendance_count }} presents</small>
            <div class="tags"><span class="tag">{{ service.preacher || 'predicateur a definir' }}</span><span class="tag gold">{{ service.worship_leader || 'louange a definir' }}</span></div>
          </article>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
