<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
defineProps({ churches: Array, items: Object });
const form = reactive({ church_id: '', title: '', preacher: '', preached_at: new Date().toISOString().slice(0, 10), bible_reference: '', media_type: 'audio', public_url: '', is_public: true, notes: '' });
const submit = () => router.post('/sermons-media', form, { preserveScroll: true });
</script>
<template>
  <AppLayout title="Sermons et media">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau media</h2>
        <label>Eglise<select v-model="form.church_id" required><option value="">Choisir</option><option v-for="c in churches" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <TextInput v-model="form.title" label="Titre" required />
        <div class="row"><TextInput v-model="form.preacher" label="Predicateur" /><TextInput v-model="form.preached_at" label="Date" type="date" required /></div>
        <div class="row"><TextInput v-model="form.bible_reference" label="Reference biblique" /><TextInput v-model="form.media_type" label="Type media" required /></div>
        <TextInput v-model="form.public_url" label="URL publique" />
        <label><input v-model="form.is_public" type="checkbox" /> Publication active</label>
        <TextInput v-model="form.notes" label="Notes" />
        <button class="btn">Publier</button>
      </form>
      <section class="panel"><h2>Bibliotheque</h2><div class="list"><article v-for="item in items.data" :key="item.id" class="item"><header><strong>{{ item.title }}</strong><small>{{ item.media_type }}</small></header><small>{{ item.church?.designation }} · {{ item.preacher }} · {{ item.bible_reference }}</small><div class="tags"><span class="tag">{{ item.preached_at }}</span><span class="tag gold">{{ item.is_public ? 'public' : 'interne' }}</span></div></article></div></section>
    </div>
  </AppLayout>
</template>
