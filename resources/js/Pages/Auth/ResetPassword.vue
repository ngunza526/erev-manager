<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({ token: String, email: String });
const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});
const submit = () => form.post('/mot-de-passe/reinitialiser', {
  preserveScroll: true,
  onFinish: () => form.reset('password', 'password_confirmation'),
});
</script>

<template>
  <main class="auth-shell">
    <section class="auth-panel">
      <div class="brand auth-brand">
        <span class="brand-mark">e</span>
        <span><strong>eReve Church</strong><small>Gestion d'eglise</small></span>
      </div>

      <div>
        <p class="eyebrow">Nouveau mot de passe</p>
        <h1>Definir un mot de passe</h1>
        <p class="muted">Minimum 10 caracteres, avec lettres et chiffres.</p>
      </div>

      <form class="form" @submit.prevent="submit">
        <TextInput
          v-model="form.email"
          label="Email"
          name="email"
          type="email"
          autocomplete="username"
          required
          :error="form.errors.email"
        />
        <TextInput
          v-model="form.password"
          label="Nouveau mot de passe"
          name="password"
          type="password"
          autocomplete="new-password"
          required
          :error="form.errors.password"
        />
        <TextInput
          v-model="form.password_confirmation"
          label="Confirmer le mot de passe"
          name="password_confirmation"
          type="password"
          autocomplete="new-password"
          required
        />
        <button class="btn" type="submit" :disabled="form.processing">
          {{ form.processing ? 'Enregistrement...' : 'Reinitialiser' }}
        </button>
        <Link href="/login" class="muted auth-hint">Retour a la connexion</Link>
      </form>
    </section>
  </main>
</template>
