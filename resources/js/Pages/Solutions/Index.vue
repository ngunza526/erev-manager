<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({ modules: Object, stats: Object });
const setStatus = (module, status) => router.patch(`/solutions/${module.id}`, { status }, { preserveScroll: true });
</script>

<template>
  <AppLayout title="Solutions">
    <section class="grid metrics">
      <article class="metric"><span>Total modules</span><strong>{{ stats.total }}</strong><span>catalogue</span></article>
      <article class="metric"><span>Coeur SaaS</span><strong>{{ stats.core }}</strong><span>prioritaires</span></article>
      <article class="metric"><span>Actifs</span><strong>{{ stats.active }}</strong><span>deploiement</span></article>
      <article class="metric"><span>Implemente</span><strong>{{ stats.implemented }}</strong><span>modules couverts</span></article>
      <article class="metric"><span>Couverture</span><strong>{{ stats.coverage }}%</strong><span>catalogue</span></article>
    </section>
    <section v-for="(items, category) in modules" :key="category" class="panel" style="margin-top:16px">
      <h2>{{ category }}</h2>
      <div class="list">
        <article v-for="module in items" :key="module.id" class="item">
          <header>
            <strong>{{ module.name }}</strong>
            <small>{{ module.church_central_reference }}</small>
          </header>
          <p class="muted">{{ module.description }}</p>
          <small><strong>Adaptation RDC:</strong> {{ module.rdc_adaptation }}</small>
          <div class="implementation">
            <strong>{{ module.implementation.label }}</strong>
            <span>{{ module.implementation.proof }}</span>
            <div class="tags">
              <Link v-if="module.implementation.path" class="tag gold" :href="module.implementation.path">Ouvrir</Link>
              <Link v-if="module.implementation.public_path" class="tag" :href="module.implementation.public_path">Flux public</Link>
            </div>
          </div>
          <div class="tags">
            <span class="tag">{{ module.implementation.level }}</span>
            <span class="tag">{{ module.status }}</span>
            <span v-if="module.is_core" class="tag gold">coeur</span>
            <button class="btn secondary" type="button" @click="setStatus(module, 'active')">Actif</button>
            <button class="btn secondary" type="button" @click="setStatus(module, 'planned')">Planifie</button>
            <button class="btn secondary" type="button" @click="setStatus(module, 'paused')">Pause</button>
          </div>
        </article>
      </div>
    </section>
  </AppLayout>
</template>
