<script setup>
import AppLayout from '../Layouts/AppLayout.vue';
defineProps({ workspace: String, metrics: Object, recentEntries: Array });
</script>

<template>
  <AppLayout title="Tableau de bord">
    <section v-if="workspace === 'communaute'" class="grid metrics">
      <article class="metric"><span>Communautes</span><strong>{{ metrics.communities }}</strong><span>coordinations</span></article>
      <article class="metric"><span>Eglises</span><strong>{{ metrics.churches }}</strong><span>rattachees</span></article>
      <article class="metric"><span>Utilisateurs</span><strong>{{ metrics.users }}</strong><span>comptes</span></article>
      <article class="metric"><span>Roles</span><strong>{{ metrics.roles }}</strong><span>permissions</span></article>
    </section>
    <section v-else class="grid metrics">
      <article class="metric"><span>Eglises</span><strong>{{ metrics.churches }}</strong><span>espace local</span></article>
      <article class="metric"><span>Membres</span><strong>{{ metrics.members }}</strong><span>registre</span></article>
      <article class="metric"><span>Effectifs</span><strong>{{ metrics.effectives }}</strong><span>utilisateurs possibles</span></article>
      <article class="metric"><span>Ecritures</span><strong>{{ metrics.entries }}</strong><span>journal</span></article>
    </section>
    <section v-if="workspace === 'eglise'" class="panel" style="margin-top:16px">
      <h2>Ecritures recentes</h2>
      <div class="list">
        <article v-for="entry in recentEntries" :key="entry.id" class="item">
          <header><strong>{{ entry.description }}</strong><small>{{ entry.currency }} · {{ entry.status }}</small></header>
          <small>{{ entry.church?.designation }} · {{ entry.reference }}</small>
        </article>
      </div>
    </section>
  </AppLayout>
</template>
