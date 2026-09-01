<script setup>
import { computed, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({ event: Object, church: Object });
const page = usePage();
const defaultExchangeRate = Number(page.props.rdc?.default_exchange_rate || 1);
const paymentMethods = computed(() => page.props.rdc?.payment_methods || { cash: 'Caisse', bank: 'Banque', card: 'Carte bancaire', mobile_money: 'Mobile Money' });
const form = reactive({ attendee_name: '', phone: '', amount_paid: props.event.ticket_price || 0, currency: props.event.currency || 'CDF', exchange_rate: defaultExchangeRate, payment_method: 'mobile_money' });
const submit = () => router.post(`/public/evenements/${props.event.id}`, form, { preserveScroll: true });
</script>

<template>
  <PublicLayout :title="event.title" :subtitle="`${church?.designation || ''} - ${event.venue || ''}`">
    <form class="form" @submit.prevent="submit">
      <div class="row">
        <TextInput v-model="form.attendee_name" label="Nom participant" required />
        <TextInput v-model="form.phone" label="Telephone" />
      </div>
      <div class="row">
        <TextInput v-model="form.amount_paid" label="Montant paye" type="number" />
        <label>Devise<select v-model="form.currency"><option value="CDF">CDF</option><option value="USD">USD</option></select></label>
      </div>
      <div class="row">
        <TextInput v-model="form.exchange_rate" label="Taux" type="number" required />
        <label>Paiement<select v-model="form.payment_method"><option v-for="(label, code) in paymentMethods" :key="code" :value="code">{{ label }}</option></select></label>
      </div>
      <button class="btn">Confirmer inscription</button>
    </form>
  </PublicLayout>
</template>
