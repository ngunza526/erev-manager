<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

defineProps({ churches: Array, groups: Object });
const form = reactive({ church_id: '', name: '', group_type: 'cellule', leader_name: '', meeting_day: '', district: '', city: '', members_count: 0 });
const submit = () => router.post('/groupes', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Groupes et cellules">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau groupe</h2>
        <label>Eglise<select v-model="form.church_id" required><option value="">Choisir</option><option v-for="c in churches" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <TextInput v-model="form.name" label="Nom" required />
        <div class="row"><TextInput v-model="form.group_type" label="Type" required /><TextInput v-model="form.leader_name" label="Responsable" required /></div>
        <div class="row"><TextInput v-model="form.meeting_day" label="Jour reunion" /><TextInput v-model="form.members_count" label="Membres" type="number" /></div>
        <div class="row"><TextInput v-model="form.district" label="Quartier" /><TextInput v-model="form.city" label="Ville" /></div>
        <button class="btn">Creer</button>
      </form>
      <section class="panel">
        <h2>Groupes actifs</h2>
        <div class="list">
          <article v-for="group in groups.data" :key="group.id" class="item">
            <header><strong>{{ group.name }}</strong><small>{{ group.group_type }}</small></header>
            <small>{{ group.church?.designation }} · {{ group.leader_name }} · {{ group.district }}, {{ group.city }}</small>
            <div class="tags"><span class="tag">{{ group.meeting_day || 'jour a definir' }}</span><span class="tag gold">{{ group.members_count }} membre(s)</span></div>
          </article>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
