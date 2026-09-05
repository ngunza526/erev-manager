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
  preacher: '',
  preached_at: new Date().toISOString().slice(0, 10),
  bible_reference: '',
  media_type: 'audio',
  public_url: '',
  is_public: true,
  notes: '',
});

const submit = () => router.post('/sermons-media', form, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Sermons et media">
    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouveau media</h2>
        <div class="pst-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>
          <TextInput v-model="form.title" label="Titre" required />
          <TextInput v-model="form.preacher" label="Predicateur" />
          <TextInput v-model="form.preached_at" label="Date" type="date" required />
          <TextInput v-model="form.bible_reference" label="Reference biblique" />
          <TextInput v-model="form.media_type" label="Type media" required />
          <TextInput v-model="form.public_url" label="URL publique" />
          <label class="check-row"><input v-model="form.is_public" type="checkbox" /><span>Publication active</span></label>
        </div>
        <button class="btn">Publier</button>
      </form>

      <section class="panel">
        <h2>Bibliotheque <small>{{ items.total }} au total</small></h2>
        <div class="pst-table-wrap">
          <table class="pst-table">
            <thead>
              <tr><th>Titre</th><th>Eglise</th><th>Type</th><th>Predicateur</th><th>Reference</th><th>Date</th><th>Publication</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in items.data" :key="item.id">
                <td><strong>{{ item.title }}</strong></td>
                <td>{{ item.church?.designation ?? '—' }}</td>
                <td>{{ item.media_type }}</td>
                <td>{{ item.preacher || '—' }}</td>
                <td>{{ item.bible_reference || '—' }}</td>
                <td>{{ item.preached_at }}</td>
                <td><span class="tag" :class="{ gold: !item.is_public }">{{ item.is_public ? 'public' : 'interne' }}</span></td>
              </tr>
              <tr v-if="!items.data.length"><td colspan="7">Aucun media.</td></tr>
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
