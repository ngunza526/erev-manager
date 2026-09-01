<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import TextInput from '../../Components/TextInput.vue';

const page = usePage();
const form = useForm({ code: '' });
const submit = () => form.post('/otp', { preserveScroll: true });
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
        <p class="muted">Saisissez le code a 6 chiffres genere pour cette tentative de connexion.</p>
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
