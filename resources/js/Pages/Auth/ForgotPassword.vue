<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TextInput from '../../Components/TextInput.vue';

const page = usePage();
const status = computed(() => page.props.flash?.success);
const form = useForm({ email: '' });
const submit = () => form.post('/mot-de-passe/oubli', { preserveScroll: true });
</script>

<template>
  <main class="auth-shell">
    <section class="auth-panel">
      <div class="brand auth-brand">
        <span class="brand-mark">e</span>
        <span><strong>eReve Church</strong><small>Gestion d'eglise</small></span>
      </div>

      <div>
        <p class="eyebrow">Mot de passe oublie</p>
        <h1>Reinitialiser l'acces</h1>
        <p class="muted">Indiquez votre email : un lien de reinitialisation vous sera envoye.</p>
      </div>

      <div v-if="status" class="flash">{{ status }}</div>

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
        <button class="btn" type="submit" :disabled="form.processing">
          {{ form.processing ? 'Envoi...' : 'Envoyer le lien' }}
        </button>
        <Link href="/login" class="muted auth-hint">Retour a la connexion</Link>
      </form>
    </section>
  </main>
</template>
