<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import TextInput from '../../Components/TextInput.vue';

const form = useForm({ email: '', password: '' });
const submit = () => form.post('/login', { preserveScroll: true });
</script>

<template>
  <main class="auth-shell">
    <section class="auth-panel">
      <div class="brand auth-brand">
        <span class="brand-mark">e</span>
        <span><strong>eReve Church</strong><small>Gestion d'eglise</small></span>
      </div>

      <div>
        <p class="eyebrow">Connexion securisee</p>
        <h1>Acceder au SaaS</h1>
        <p class="muted">Connectez-vous avec votre compte, puis confirmez le code OTP de votre session.</p>
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
          label="Mot de passe"
          name="password"
          type="password"
          autocomplete="current-password"
          required
          :error="form.errors.password"
        />
        <button class="btn" type="submit" :disabled="form.processing">
          {{ form.processing ? 'Verification...' : 'Continuer' }}
        </button>
        <Link href="/mot-de-passe/oubli" class="muted auth-hint">Mot de passe oublie ?</Link>
      </form>

      <p class="muted auth-hint">Utilisez le compte fourni par votre administrateur.</p>
    </section>
  </main>
</template>
