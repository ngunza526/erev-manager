<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

defineProps({ communities: Object });
const form = reactive({ designation: '', headquarters_number: '', headquarters_avenue: '', headquarters_district: '', headquarters_city: '', headquarters_province: '', headquarters_country: 'RDC', authorization_number: '', email: '', website: '', phone: '' });
const submit = () => router.post('/communautes', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Communautes">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvelle communaute</h2>
        <TextInput v-model="form.designation" label="Designation" required />
        <div class="row"><TextInput v-model="form.headquarters_number" label="Numero siege" /><TextInput v-model="form.headquarters_avenue" label="Avenue siege" /></div>
        <TextInput v-model="form.headquarters_district" label="Quartier siege" />
        <div class="row"><TextInput v-model="form.headquarters_city" label="Ville siege" required /><TextInput v-model="form.headquarters_province" label="Province" required /></div>
        <TextInput v-model="form.headquarters_country" label="Pays" required />
        <TextInput v-model="form.authorization_number" label="Numero autorisation" required />
        <div class="row"><TextInput v-model="form.email" label="Email" type="email" /><TextInput v-model="form.website" label="Site web" type="url" /></div>
        <TextInput v-model="form.phone" label="Telephone" />
        <button class="btn">Enregistrer</button>
      </form>
      <section class="panel">
        <h2>Registre</h2>
        <div class="list">
          <article v-for="community in communities.data" :key="community.id" class="item">
            <header><strong>{{ community.designation }}</strong><small>{{ community.authorization_number }}</small></header>
            <small>{{ community.headquarters_number || 's/n' }} {{ community.headquarters_avenue || '' }} - {{ community.headquarters_district || 'quartier n/a' }} - {{ community.headquarters_city }}, {{ community.headquarters_province }} - {{ community.phone }}</small>
            <div class="tags"><span class="tag">{{ community.churches_count }} eglise(s)</span><span class="tag gold">active</span></div>
          </article>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
