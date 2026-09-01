<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

defineProps({ churches: Object, communities: Array });
const form = reactive({ community_id: '', designation: '', address_number: '', address_avenue: '', address_district: '', address_city: '', address_province: '', address_country: 'RDC', email: '', phone: '' });
const submit = () => router.post('/eglises', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Eglises">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvelle eglise</h2>
        <label>Communaute<select v-model="form.community_id" required><option value="">Choisir</option><option v-for="c in communities" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <TextInput v-model="form.designation" label="Designation" required />
        <div class="row"><TextInput v-model="form.address_number" label="Numero" /><TextInput v-model="form.address_avenue" label="Avenue" /></div>
        <div class="row"><TextInput v-model="form.address_city" label="Ville" required /><TextInput v-model="form.address_province" label="Province" required /></div>
        <TextInput v-model="form.address_district" label="Quartier" required />
        <TextInput v-model="form.address_country" label="Pays" required />
        <div class="row"><TextInput v-model="form.email" label="Email" type="email" /><TextInput v-model="form.phone" label="Telephone" /></div>
        <button class="btn">Rattacher</button>
      </form>
      <section class="panel">
        <h2>Eglises locales</h2>
        <div class="list">
          <article v-for="church in churches.data" :key="church.id" class="item">
            <header><strong>{{ church.designation }}</strong><small>{{ church.address_city }}</small></header>
            <small>{{ church.community?.designation }} - {{ church.address_number || 's/n' }} {{ church.address_avenue || '' }} - {{ church.address_district }} - {{ church.phone }}</small>
            <div class="tags"><span class="tag">{{ church.members_count }} membre(s)</span><span class="tag gold">rattachee</span></div>
          </article>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
