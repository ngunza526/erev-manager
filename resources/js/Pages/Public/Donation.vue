<script setup>
import { computed, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({ church: Object });
const page = usePage();
const paymentMethods = computed(() => page.props.rdc?.payment_methods || { cash: 'Caisse', bank: 'Banque', card: 'Carte bancaire', mobile_money: 'Mobile Money' });
// SEC-27 : le taux de change n'est plus saisi cote public, il est fixe par le serveur.
const form = reactive({ giver_name: '', type: 'don', amount: 0, currency: 'CDF', payment_method: 'mobile_money', phone: '' });
const submit = () => router.post(`/public/eglises/${props.church.id}/don`, form, { preserveScroll: true });
</script>

<template>
  <PublicLayout title="Contribution" :subtitle="church.designation">
    <form class="form" @submit.prevent="submit">
      <div class="row">
        <TextInput v-model="form.giver_name" label="Nom donateur" />
        <TextInput v-model="form.phone" label="Telephone / reference paiement" />
      </div>
      <label>Type<select v-model="form.type"><option value="don">Don</option><option value="offrande">Offrande</option><option value="dime">Dime</option></select></label>
      <div class="row">
        <TextInput v-model="form.amount" label="Montant" type="number" required />
        <label>Devise<select v-model="form.currency"><option value="CDF">CDF</option><option value="USD">USD</option></select></label>
      </div>
      <label>Paiement<select v-model="form.payment_method"><option v-for="(label, code) in paymentMethods" :key="code" :value="code">{{ label }}</option></select></label>
      <button class="btn">Valider la contribution</button>
    </form>
  </PublicLayout>
</template>
