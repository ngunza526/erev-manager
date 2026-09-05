<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';
import Pagination from '../../Components/Pagination.vue';

defineProps({ churches: Array, items: Object });

const form = reactive({
  church_id: '',
  title: '',
  category: 'formation',
  instructor_name: '',
  starts_at: '',
  ends_at: '',
  enrollments_count: 0,
  certificate_enabled: true,
});

const submit = () => router.post('/formations', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Formations">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau cours</h2>
        <div class="pst-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <TextInput v-model="form.title" label="Titre" required />
          <TextInput v-model="form.category" label="Categorie" required />
          <TextInput v-model="form.instructor_name" label="Formateur" required />
          <TextInput v-model="form.starts_at" label="Debut" type="date" required />
          <TextInput v-model="form.ends_at" label="Fin" type="date" />
          <TextInput v-model="form.enrollments_count" label="Inscriptions" type="number" />
          <label class="check-row"><input v-model="form.certificate_enabled" type="checkbox" /><span>Certificat actif</span></label>
        </div>
        <button class="btn">Creer</button>
      </form>

      <section class="panel">
        <h2>Cours <small>{{ items.total }} au total</small></h2>
        <div class="pst-table-wrap">
          <table class="pst-table">
            <thead>
              <tr><th>Titre</th><th>Eglise</th><th>Categorie</th><th>Formateur</th><th>Debut</th><th>Inscrits</th><th>Certificat</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in items.data" :key="item.id">
                <td><strong>{{ item.title }}</strong></td>
                <td>{{ item.church?.designation ?? '—' }}</td>
                <td>{{ item.category }}</td>
                <td>{{ item.instructor_name }}</td>
                <td>{{ item.starts_at }}</td>
                <td>{{ item.enrollments_count }}</td>
                <td><span class="tag" :class="{ gold: !item.certificate_enabled }">{{ item.certificate_enabled ? 'certificat' : 'sans certificat' }}</span></td>
              </tr>
              <tr v-if="!items.data.length"><td colspan="7">Aucun cours.</td></tr>
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
