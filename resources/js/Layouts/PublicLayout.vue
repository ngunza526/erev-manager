<script setup>
defineProps({ title: String, subtitle: String });
</script>

<template>
  <main class="public-shell">
    <section class="public-panel">
      <p class="eyebrow">eReve Church RDC</p>
      <h1>{{ title }}</h1>
      <p class="muted">{{ subtitle }}</p>
      <div v-if="$page.props.flash?.success" class="flash">{{ $page.props.flash.success }}</div>
      <slot />
    </section>
  </main>
</template>
