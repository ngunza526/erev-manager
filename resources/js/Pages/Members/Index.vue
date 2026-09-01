<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

defineProps({ members: Object, churches: Array, statuses: Array });
const form = reactive({ church_id: '', last_name: '', middle_name: '', first_name: '', sex: 'Masculin', birth_date: '', birth_place: '', profession: '', marital_status: 'Celibataire', spouse: '' });
const submit = () => router.post('/membres', form, { preserveScroll: true });
const promote = (member, status) => router.patch(`/membres/${member.id}/statut`, { status }, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Membres">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau membre</h2>
        <label>Eglise<select v-model="form.church_id" required><option value="">Choisir</option><option v-for="c in churches" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <div class="row"><TextInput v-model="form.last_name" label="Nom" required /><TextInput v-model="form.middle_name" label="Postnom" required /></div>
        <TextInput v-model="form.first_name" label="Prenom" required />
        <div class="row"><label>Sexe<select v-model="form.sex"><option>Masculin</option><option>Feminin</option></select></label><TextInput v-model="form.birth_date" label="Date naissance" type="date" required /></div>
        <TextInput v-model="form.birth_place" label="Lieu naissance" required />
        <TextInput v-model="form.profession" label="Profession" required />
        <label>Etat civil<select v-model="form.marital_status"><option>Celibataire</option><option>Marie(e)</option><option>Veuf/Veuve</option></select></label>
        <TextInput v-model="form.spouse" label="Conjoint si marie(e)" />
        <button class="btn">Creer comme sympathisant</button>
      </form>
      <section class="panel">
        <h2>Registre</h2>
        <div class="list">
          <article v-for="member in members.data" :key="member.id" class="item">
            <header><strong>{{ member.last_name }} {{ member.middle_name }} {{ member.first_name }}</strong><small>{{ member.status }}</small></header>
            <small>{{ member.church?.designation }} · {{ member.profession }} · {{ member.birth_place }}</small>
            <div class="tags">
              <button v-for="status in statuses" :key="status" class="btn secondary" type="button" @click="promote(member, status)">{{ status }}</button>
            </div>
          </article>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
