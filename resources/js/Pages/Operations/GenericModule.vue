<script setup>
import { computed, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';

const props = defineProps({
  moduleKey: String,
  module: Object,
  churches: Array,
  options: { type: Object, default: () => ({}) },
  items: Object,
});

const page = usePage();
const defaultExchangeRate = Number(page.props.rdc?.default_exchange_rate || 1);
const paymentMethods = computed(() => page.props.rdc?.payment_methods || { cash: 'Caisse', bank: 'Banque', card: 'Carte bancaire', mobile_money: 'Mobile Money' });
const initialValue = (field) => {
  if (field.type === 'checkbox') return Boolean(field.default);
  if (field.name === 'exchange_rate') return defaultExchangeRate;
  if (field.type === 'select') return field.default ?? '';
  if (field.type === 'number') return field.default ?? 0;
  if (field.type === 'date') return field.default ?? new Date().toISOString().slice(0, 10);
  if (field.type === 'datetime-local') return field.default ?? '';
  return field.default ?? '';
};

const form = reactive({
  church_id: '',
  ...Object.fromEntries(props.module.fields.map((field) => [field.name, initialValue(field)])),
});

const submit = () => router.post(`/${props.moduleKey}`, form, { preserveScroll: true });
const valueFor = (item, key) => item?.[key] ?? '';
const formatDate = (value) => (value ? new Date(value).toLocaleDateString('fr-FR') : '—');
const today = () => new Date().toISOString().slice(0, 10);
const payItem = (item) => {
  if (props.moduleKey === 'fournisseurs') {
    router.post(`/fournisseurs/${item.id}/payer`, { payment_method: item.payment_method || 'bank' }, { preserveScroll: true });
  }
  if (props.moduleKey === 'paie') {
    router.post(`/paie/${item.id}/payer`, { payment_method: item.payment_method || 'bank', paid_at: item.paid_at || today() }, { preserveScroll: true });
  }
};
const canPay = (item) => ['fournisseurs', 'paie'].includes(props.moduleKey) && !item.journal_entry_id && item.status !== 'paid';
const counselingState = reactive({});
const counselingFor = (item) => {
  if (!counselingState[item.id]) {
    counselingState[item.id] = {
      appointment_date: item.appointment_date || today(),
      next_follow_up_at: item.next_follow_up_at || today(),
      assigned_to: item.assigned_to || '',
      last_follow_up_note: item.last_follow_up_note || 'Suivi pastoral confidentiel.',
    };
  }
  return counselingState[item.id];
};
const scheduleCounseling = (item) => router.post(`/counseling/${item.id}/planifier`, counselingFor(item), { preserveScroll: true });
const closeCounseling = (item) => router.post(`/counseling/${item.id}/cloturer`, { last_follow_up_note: counselingFor(item).last_follow_up_note }, { preserveScroll: true });
</script>

<template>
  <AppLayout :title="module.title">
    <section class="hero">
      <p class="eyebrow">Module operationnel</p>
      <strong>{{ module.title }}</strong>
      <p>{{ module.description }}</p>
    </section>

    <div class="grid">
      <form class="panel form" @submit.prevent="submit">
        <h2>Nouvelle saisie</h2>
        <div class="gm-grid">
          <label>
            Eglise
            <select v-model="form.church_id" required>
              <option value="">Choisir</option>
              <option v-for="church in churches" :key="church.id" :value="church.id">{{ church.designation }}</option>
            </select>
          </label>

          <template v-for="field in module.fields" :key="field.name">
            <label v-if="field.type === 'textarea'" class="gm-span">
              {{ field.label }}
              <textarea v-model="form[field.name]" :required="field.required"></textarea>
            </label>
            <label v-else-if="field.type === 'select'">
              {{ field.label }}
              <select v-model="form[field.name]" :required="field.required">
                <option value="">Choisir</option>
                <option v-for="option in (options[field.optionsKey] || [])" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </label>
            <label v-else-if="field.name === 'payment_method'">
              {{ field.label }}
              <select v-model="form[field.name]" :required="field.required">
                <option v-for="(label, code) in paymentMethods" :key="code" :value="code">{{ label }}</option>
              </select>
            </label>
            <label v-else-if="field.type === 'checkbox'" class="check-row">
              <input v-model="form[field.name]" type="checkbox" />
              <span>{{ field.label }}</span>
            </label>
            <label v-else>
              {{ field.label }}
              <input v-model="form[field.name]" :type="field.type || 'text'" :required="field.required" />
            </label>
          </template>
        </div>

        <button class="btn">Enregistrer</button>
      </form>

      <section class="panel">
        <h2>Registre <small>{{ items.total }} au total</small></h2>
        <div class="gm-table-wrap">
          <table class="gm-table">
            <thead>
              <tr>
                <th>#</th>
                <th>{{ module.primary }}</th>
                <th>{{ module.secondary }}</th>
                <th>{{ module.badge }}</th>
                <th>Eglise</th>
                <th>Cree le</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items.data" :key="item.id">
                <td>#{{ item.id }}</td>
                <td><strong>{{ valueFor(item, module.primary) }}</strong></td>
                <td>{{ valueFor(item, module.secondary) }}</td>
                <td>{{ valueFor(item, module.badge) }}</td>
                <td>{{ item.church?.designation ?? '—' }}</td>
                <td>{{ formatDate(item.created_at) }}</td>
                <td>
                  <div class="gm-actions">
                    <span v-if="item.journal_entry_id" class="tag">ecriture #{{ item.journal_entry_id }}</span>
                    <span v-if="moduleKey === 'counseling' && item.confidentiality_level" class="tag">{{ item.confidentiality_level }}</span>
                    <button v-if="canPay(item)" class="btn secondary sm" type="button" @click="payItem(item)">Payer et comptabiliser</button>

                    <div v-if="moduleKey === 'counseling'" class="workflow-actions">
                      <input v-model="counselingFor(item).appointment_date" type="date" />
                      <input v-model="counselingFor(item).next_follow_up_at" type="date" />
                      <input v-model="counselingFor(item).assigned_to" placeholder="Accompagnateur" />
                      <input v-model="counselingFor(item).last_follow_up_note" placeholder="Note confidentielle" />
                      <button class="btn secondary sm" type="button" @click="scheduleCounseling(item)">Planifier</button>
                      <button class="btn secondary sm" type="button" @click="closeCounseling(item)">Cloturer</button>
                    </div>
                  </div>
                </td>
              </tr>
              <tr v-if="!items.data.length"><td colspan="7">Aucune saisie.</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="items" />
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.gm-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  align-items: end;
}

.gm-span { grid-column: 1 / -1; }

.gm-table-wrap { overflow-x: auto; }
.gm-table { width: 100%; border-collapse: collapse; }
.gm-table th,
.gm-table td {
  text-align: left;
  padding: 9px 10px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  vertical-align: middle;
}
.gm-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; font-weight: 950; white-space: nowrap; }
.gm-table tr:last-child td { border-bottom: 0; }

.gm-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.btn.sm { min-height: 30px; padding: 0 10px; font-size: 12px; }
</style>
