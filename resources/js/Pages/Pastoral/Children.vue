<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

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
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvel enfant</h2>
        <div class="pst-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <TextInput v-model="form.full_name" label="Nom enfant" required />
          <TextInput v-model="form.birth_date" label="Date naissance" type="date" required />
          <TextInput v-model="form.guardian_name" label="Responsable" required />
          <TextInput v-model="form.guardian_phone" label="Telephone responsable" />
          <TextInput v-model="form.classroom" label="Classe" />
          <TextInput v-model="form.check_in_code" label="Code securite" />
          <label class="check-row"><input v-model="form.checked_in" type="checkbox" /><span>Check-in actif</span></label>
        </div>
        <button class="btn">Enregistrer</button>
      </form>

      <section class="panel">
        <h2>Check-in enfants <small>{{ items.total }} au total</small></h2>
        <div class="pst-table-wrap">
          <table class="pst-table">
            <thead>
              <tr><th>Enfant</th><th>Responsable</th><th>Classe</th><th>Etat</th><th>Securite / sortie</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in items.data" :key="item.id">
                <td><strong>{{ item.full_name }}</strong></td>
                <td>{{ item.guardian_name }} · {{ item.guardian_phone || 's/n' }}</td>
                <td>{{ item.classroom || 'a definir' }}</td>
                <td>
                  <span class="tag" :class="{ gold: !item.checked_in }">{{ item.checked_in ? 'present' : 'sorti' }}</span>
                  <div class="pst-meta">
                    <small v-if="item.checked_in_at">Entree {{ new Date(item.checked_in_at).toLocaleTimeString() }}</small>
                    <small v-if="item.released_to">Sortie : {{ item.released_to }}</small>
                  </div>
                </td>
                <td class="child-actions">
                  <input v-model="stateFor(item).code" placeholder="Code securite" />
                  <input v-model="stateFor(item).released_to" placeholder="Remis a" />
                  <button class="btn secondary sm" type="button" @click="checkIn(item)">Entree</button>
                  <button class="btn secondary sm" type="button" @click="checkOut(item)">Sortie</button>
                </td>
              </tr>
              <tr v-if="!items.data.length"><td colspan="5">Aucun enfant enregistre.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="items" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.pst-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.pst-table-wrap { overflow-x: auto; }
.pst-table { width: 100%; border-collapse: collapse; }
.pst-table th,
.pst-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  vertical-align: middle;
}
.pst-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; white-space: nowrap; }
.pst-table tr:last-child td { border-bottom: 0; }
.pst-meta { display: flex; flex-direction: column; gap: 2px; margin-top: 4px; color: var(--muted); }
.child-actions { min-width: 320px; }
.btn.sm { min-height: 30px; padding: 0 10px; font-size: 12px; }
</style>
