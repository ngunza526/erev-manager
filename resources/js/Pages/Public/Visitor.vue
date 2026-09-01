<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({ church: Object });
const form = reactive({ full_name: '', phone: '', email: '', visit_source: 'qr accueil', notes: '' });
const submit = () => router.post(`/public/eglises/${props.church.id}/visiteur`, form, { preserveScroll: true });
</script>

<template>
  <PublicLayout title="Bienvenue" :subtitle="church.designation">
    <form class="form" @submit.prevent="submit">
      <TextInput v-model="form.full_name" label="Nom complet" required />
      <div class="row">
        <TextInput v-model="form.phone" label="Telephone" />
        <TextInput v-model="form.email" label="Email" type="email" />
      </div>
      <TextInput v-model="form.visit_source" label="Source visite" required />
      <label>Message<textarea v-model="form.notes"></textarea></label>
      <button class="btn">Enregistrer ma visite</button>
    </form>
  </PublicLayout>
</template>
