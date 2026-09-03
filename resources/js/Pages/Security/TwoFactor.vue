<script setup>
import { ref, watchEffect } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import QRCode from 'qrcode';
import AppLayout from '../../Layouts/AppLayout.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({ enabled: Boolean, pending: Object });
const page = usePage();
const status = () => page.props.flash?.success;

const qr = ref('');
watchEffect(() => {
  if (props.pending?.uri) {
    QRCode.toDataURL(props.pending.uri, { margin: 1, width: 220 }).then((url) => { qr.value = url; });
  } else {
    qr.value = '';
  }
});

const startForm = useForm({});
const start = () => startForm.post('/securite/authentification', { preserveScroll: true });

const confirmForm = useForm({ code: '' });
const confirm = () => confirmForm.post('/securite/authentification/confirmer', {
  preserveScroll: true,
  onSuccess: () => confirmForm.reset(),
});

const disableForm = useForm({ password: '' });
const disable = () => disableForm.delete('/securite/authentification', {
  preserveScroll: true,
  onSuccess: () => disableForm.reset(),
});
</script>

<template>
  <AppLayout title="Authentification a deux facteurs">
    <div class="grid">
      <div v-if="status()" class="flash">{{ status() }}</div>

      <section class="panel">
        <h2>Application d'authentification (TOTP)</h2>
        <p class="muted">
          Une fois activee, la connexion demande le code a 6 chiffres de votre application
          (Google Authenticator, Authy, FreeOTP...) a la place du code par email.
        </p>

        <template v-if="enabled">
          <p class="tag">Active</p>
          <form class="form" @submit.prevent="disable">
            <p class="muted">Pour desactiver, confirmez votre mot de passe.</p>
            <TextInput
              v-model="disableForm.password"
              label="Mot de passe"
              name="password"
              type="password"
              autocomplete="current-password"
              required
              :error="disableForm.errors.password"
            />
            <button class="btn secondary" type="submit" :disabled="disableForm.processing">Desactiver</button>
          </form>
        </template>

        <template v-else-if="pending">
          <ol class="muted">
            <li>Scannez ce QR code dans votre application d'authentification.</li>
            <li>Ou saisissez la cle manuellement : <code>{{ pending.secret }}</code></li>
            <li>Entrez le code affiche pour confirmer.</li>
          </ol>
          <img v-if="qr" :src="qr" alt="QR code TOTP" width="220" height="220" />
          <form class="form" @submit.prevent="confirm">
            <TextInput
              v-model="confirmForm.code"
              label="Code a 6 chiffres"
              name="code"
              inputmode="numeric"
              autocomplete="one-time-code"
              maxlength="6"
              required
              :error="confirmForm.errors.code"
            />
            <button class="btn" type="submit" :disabled="confirmForm.processing">Confirmer l'activation</button>
          </form>
        </template>

        <template v-else>
          <button class="btn" type="button" :disabled="startForm.processing" @click="start">
            Activer l'application d'authentification
          </button>
        </template>
      </section>
    </div>
  </AppLayout>
</template>
