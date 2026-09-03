<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({ method: { type: String, default: 'email' } });
const page = usePage();
const form = useForm({ code: '' });
const submit = () => form.post('/otp', { preserveScroll: true });

const hint = computed(() => props.method === 'totp'
  ? "Saisissez le code a 6 chiffres affiche par votre application d'authentification."
  : 'Saisissez le code a 6 chiffres genere pour cette tentative de connexion.');
</script>

<template>
  <main class="auth-shell">
    <section class="auth-panel">
      <div class="brand auth-brand">
        <span class="brand-mark">e</span>
        <span><strong>eReve Church</strong><small>Validation OTP</small></span>
      </div>

      <div>
        <p class="eyebrow">Verification</p>
        <h1>Code OTP</h1>
        <p class="muted">{{ hint }}</p>
      </div>

      <p v-if="page.props.flash?.success" class="flash">{{ page.props.flash.success }}</p>

      <form class="form" @submit.prevent="submit">
        <TextInput
          v-model="form.code"
          label="Code a 6 chiffres"
          name="code"
          inputmode="numeric"
          autocomplete="one-time-code"
          maxlength="6"
          required
          :error="form.errors.code"
        />
        <button class="btn" type="submit" :disabled="form.processing">
          {{ form.processing ? 'Validation...' : 'Valider' }}
        </button>
      </form>
    </section>
  </main>
</template>
