<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
  perPage: { type: Number, default: 10 },
});

const page = ref(1);

const total = computed(() => props.items.length);
const pageCount = computed(() => Math.max(1, Math.ceil(total.value / props.perPage)));
const from = computed(() => (total.value === 0 ? 0 : (page.value - 1) * props.perPage + 1));
const to = computed(() => Math.min(page.value * props.perPage, total.value));
const pageItems = computed(() => props.items.slice((page.value - 1) * props.perPage, page.value * props.perPage));
const pages = computed(() => Array.from({ length: pageCount.value }, (_, index) => index + 1));

watch(pageCount, (count) => {
  if (page.value > count) {
    page.value = count;
  }
});
</script>

<template>
  <div class="cpg">
    <slot :items="pageItems" :from="from" :to="to" :total="total" />

    <div class="pg">
      <span class="pg-info">
        <template v-if="total">Affichage de {{ from }} a {{ to }} sur {{ total }}</template>
        <template v-else>Aucun resultat</template>
      </span>

      <nav v-if="pageCount > 1" class="pg-links" aria-label="Pagination">
        <button type="button" class="pg-link" :disabled="page === 1" @click="page--">&lsaquo;</button>
        <button
          v-for="value in pages"
          :key="value"
          type="button"
          class="pg-link"
          :class="{ 'is-active': value === page }"
          @click="page = value"
        >
          {{ value }}
        </button>
        <button type="button" class="pg-link" :disabled="page === pageCount" @click="page++">&rsaquo;</button>
      </nav>
    </div>
  </div>
</template>

<style scoped>
.cpg { display: grid; gap: 12px; }

.pg {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
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

.pg-link:hover:not(:disabled) {
  border-color: var(--blue);
  background: var(--blue-soft);
  color: var(--blue-dark);
}

.pg-link.is-active {
  background: var(--blue);
  border-color: var(--blue);
  color: #fff;
}

.pg-link:disabled { opacity: .5; cursor: not-allowed; }
</style>
