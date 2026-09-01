<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

defineProps({ churches: Array, items: Object });

const form = reactive({ church_id: '', full_name: '', birth_date: '', guardian_name: '', guardian_phone: '', classroom: '', check_in_code: '', checked_in: false });
const security = reactive({});
const submit = () => router.post('/enfants', form, { preserveScroll: true });
const stateFor = (item) => {
  if (!security[item.id]) security[item.id] = { code: item.check_in_code || '', released_to: item.guardian_name || '' };
  return security[item.id];
};
const checkIn = (item) => router.post(`/enfants/${item.id}/check-in`, { check_in_code: stateFor(item).code }, { preserveScroll: true });
const checkOut = (item) => router.post(`/enfants/${item.id}/check-out`, { check_in_code: stateFor(item).code, released_to: stateFor(item).released_to }, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Eglise des enfants">
    <div class="grid two">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvel enfant</h2>
        <label>Eglise<select v-model="form.church_id" required><option value="">Choisir</option><option v-for="c in churches" :key="c.id" :value="c.id">{{ c.designation }}</option></select></label>
        <TextInput v-model="form.full_name" label="Nom enfant" required />
        <TextInput v-model="form.birth_date" label="Date naissance" type="date" required />
        <div class="row"><TextInput v-model="form.guardian_name" label="Responsable" required /><TextInput v-model="form.guardian_phone" label="Telephone responsable" /></div>
        <div class="row"><TextInput v-model="form.classroom" label="Classe" /><TextInput v-model="form.check_in_code" label="Code securite" /></div>
        <label class="check-row"><input v-model="form.checked_in" type="checkbox" /><span>Check-in actif</span></label>
        <button class="btn">Enregistrer</button>
      </form>

      <section class="panel">
        <h2>Check-in enfants</h2>
        <div class="list">
          <article v-for="item in items.data" :key="item.id" class="item">
            <header><strong>{{ item.full_name }}</strong><small>{{ item.checked_in ? 'present' : 'sorti' }}</small></header>
            <small>{{ item.church?.designation }} - {{ item.guardian_name }} - {{ item.guardian_phone }}</small>
            <div class="tags">
              <span class="tag">{{ item.classroom || 'classe a definir' }}</span>
              <span class="tag gold">{{ item.check_in_code || 'sans code' }}</span>
              <span v-if="item.checked_in_at" class="tag">Entree {{ new Date(item.checked_in_at).toLocaleTimeString() }}</span>
              <span v-if="item.released_to" class="tag">Sortie: {{ item.released_to }}</span>
            </div>
            <div class="child-actions">
              <input v-model="stateFor(item).code" placeholder="Code securite" />
              <input v-model="stateFor(item).released_to" placeholder="Remis a" />
              <button class="btn secondary" type="button" @click="checkIn(item)">Entree</button>
              <button class="btn secondary" type="button" @click="checkOut(item)">Sortie</button>
            </div>
          </article>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
