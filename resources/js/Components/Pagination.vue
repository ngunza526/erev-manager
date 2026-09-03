<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  // Paginateur Laravel serialise : total, from, to, current_page, last_page, links[].
  meta: { type: Object, required: true },
});

const links = computed(() => props.meta?.links ?? []);
const multiPage = computed(() => (props.meta?.last_page ?? 1) > 1);

const go = (url) => url && router.get(url, {}, { preserveScroll: true, preserveState: true });
</script>

<template>
  <div class="pg">
    <span class="pg-info">
      <template v-if="meta.total">Affichage de {{ meta.from }} a {{ meta.to }} sur {{ meta.total }}</template>
      <template v-else>Aucun resultat</template>
    </span>

    <nav v-if="multiPage" class="pg-links" aria-label="Pagination">
      <button
        v-for="(link, index) in links"
        :key="index"
        type="button"
        class="pg-link"
        :class="{ 'is-active': link.active, 'is-disabled': !link.url }"
        :disabled="!link.url"
        @click="go(link.url)"
        v-html="link.label"
      />
    </nav>
  </div>
</template>

<style scoped>
.pg {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 14px;
}

.pg-info { color: var(--muted); font-size: 13px; font-weight: 800; }

.pg-links { display: flex; flex-wrap: wrap; gap: 4px; }

.pg-link {
  min-width: 34px;
  min-height: 34px;
  padding: 0 8px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
  color: var(--ink);
  font-weight: 850;
  cursor: pointer;
}

.pg-link:hover:not(.is-disabled) {
  border-color: var(--blue);
  background: var(--blue-soft);
  color: var(--blue-dark);
}

.pg-link.is-active {
  background: var(--blue);
  border-color: var(--blue);
  color: #fff;
}

.pg-link.is-disabled { opacity: .5; cursor: not-allowed; }
</style>
