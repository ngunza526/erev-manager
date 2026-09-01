<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

defineProps({ churches: Array, events: Object });
const form = reactive({ church_id: '', title: '', event_type: 'conference', starts_at: '', ends_at: '', venue: '', currency: 'CDF', ticket_price: 0, capacity: '', registrations_count: 0, is_public: true });
const submit = () => router.post('/evenements', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Evenements">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvel evenement</h2>
        <label>Eglise<select v-model="form.church_id" required><option value="">Choisir</option><option v-for="c in churches" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <TextInput v-model="form.title" label="Titre" required />
        <div class="row"><TextInput v-model="form.event_type" label="Type" required /><TextInput v-model="form.venue" label="Lieu" required /></div>
        <div class="row"><TextInput v-model="form.starts_at" label="Debut" type="datetime-local" required /><TextInput v-model="form.ends_at" label="Fin" type="datetime-local" /></div>
        <div class="row"><label>Devise<select v-model="form.currency"><option>USD</option><option>CDF</option></select></label><TextInput v-model="form.ticket_price" label="Prix ticket" type="number" /></div>
        <div class="row"><TextInput v-model="form.capacity" label="Capacite" type="number" /><TextInput v-model="form.registrations_count" label="Inscriptions" type="number" /></div>
        <button class="btn">Creer</button>
      </form>
      <section class="panel">
        <h2>Calendrier</h2>
        <div class="list">
          <article v-for="event in events.data" :key="event.id" class="item">
            <header><strong>{{ event.title }}</strong><small>{{ event.event_type }}</small></header>
            <small>{{ event.church?.designation }} · {{ event.venue }} · {{ event.starts_at }}</small>
            <div class="tags"><span class="tag">{{ event.ticket_price }} {{ event.currency }}</span><span class="tag gold">{{ event.registrations_count }} inscrit(s)</span></div>
          </article>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
