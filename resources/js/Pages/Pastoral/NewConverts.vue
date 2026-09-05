<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ churches: Array, items: Object });

const form = reactive({
  church_id: '',
  full_name: '',
  conversion_date: new Date().toISOString().slice(0, 10),
  discipleship_stage: 'accueil',
  mentor_name: '',
  baptism_target_date: '',
  status: 'en_suivi',
  notes: '',
});

const submit = () => router.post('/convertis', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Nouveaux convertis">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau converti</h2>
        <div class="pst-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <TextInput v-model="form.full_name" label="Nom complet" required />
          <TextInput v-model="form.conversion_date" label="Date conversion" type="date" required />
          <TextInput v-model="form.baptism_target_date" label="Bapteme prevu" type="date" />
          <TextInput v-model="form.discipleship_stage" label="Etape discipleship" required />
          <TextInput v-model="form.mentor_name" label="Mentor" />
          <TextInput v-model="form.status" label="Statut" required />
        </div>
        <button class="btn">Enregistrer</button>
      </form>

      <section class="panel">
        <h2>Parcours <small>{{ items.total }} au total</small></h2>
        <div class="pst-table-wrap">
          <table class="pst-table">
            <thead>
              <tr><th>Nom</th><th>Eglise</th><th>Date conversion</th><th>Etape</th><th>Mentor</th><th>Statut</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in items.data" :key="item.id">
                <td><strong>{{ item.full_name }}</strong></td>
                <td>{{ item.church?.designation ?? '—' }}</td>
                <td>{{ item.conversion_date }}</td>
                <td>{{ item.discipleship_stage }}</td>
                <td>{{ item.mentor_name || 'a definir' }}</td>
                <td><span class="tag">{{ item.status }}</span></td>
              </tr>
              <tr v-if="!items.data.length"><td colspan="6">Aucun converti.</td></tr>
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
  white-space: nowrap;
}
.pst-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; }
.pst-table tr:last-child td { border-bottom: 0; }
</style>
