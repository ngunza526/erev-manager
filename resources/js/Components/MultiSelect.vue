<script setup>
import { computed, nextTick, ref } from 'vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  options: { type: Array, default: () => [] },
  label: String,
  placeholder: { type: String, default: 'Rechercher et selectionner...' },
});
const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const query = ref('');
const active = ref(-1);
const root = ref(null);
const input = ref(null);

const selected = computed(() => props.modelValue);
const available = computed(() => {
  const q = query.value.trim().toLowerCase();
  return props.options
    .filter((option) => !selected.value.includes(option))
    .filter((option) => !q || String(option).toLowerCase().includes(q));
});

const add = (option) => {
  if (!option || selected.value.includes(option)) {
    return;
  }
  emit('update:modelValue', [...selected.value, option]);
  query.value = '';
  active.value = -1;
  nextTick(() => input.value?.focus());
};

const remove = (option) => emit('update:modelValue', selected.value.filter((item) => item !== option));

const removeLast = () => {
  if (!query.value && selected.value.length) {
    remove(selected.value[selected.value.length - 1]);
  }
};

const onEnter = () => {
  if (active.value >= 0 && available.value[active.value]) {
    add(available.value[active.value]);
  } else if (available.value.length === 1) {
    add(available.value[0]);
  }
};

const move = (delta) => {
  if (!available.value.length) {
    return;
  }
  active.value = (active.value + delta + available.value.length) % available.value.length;
};

const closeOnBlur = (event) => {
  if (!root.value?.contains(event.relatedTarget)) {
    open.value = false;
    active.value = -1;
  }
};
</script>

<template>
  <label ref="root" class="ms" @focusout="closeOnBlur">
    <span v-if="label">{{ label }}</span>

    <div class="ms-box" :class="{ 'is-open': open }" @click="input?.focus()">
      <span v-for="option in selected" :key="option" class="ms-chip">
        {{ option }}
        <button type="button" class="ms-x" aria-label="Retirer" @click.stop="remove(option)">&times;</button>
      </span>
      <input
        ref="input"
        v-model="query"
        class="ms-input"
        :placeholder="selected.length ? '' : placeholder"
        autocomplete="off"
        @focus="open = true"
        @keydown.enter.prevent="onEnter"
        @keydown.down.prevent="move(1)"
        @keydown.up.prevent="move(-1)"
        @keydown.backspace="removeLast"
        @keydown.esc="open = false"
      />
    </div>

    <div v-if="open" class="ms-menu">
      <button
        v-for="(option, index) in available"
        :key="option"
        type="button"
        class="ms-opt"
        :class="{ 'is-active': index === active }"
        @mousedown.prevent="add(option)"
        @mousemove="active = index"
      >
        {{ option }}
      </button>
      <p v-if="!available.length" class="ms-empty">Aucune option disponible</p>
    </div>
  </label>
</template>

<style scoped>
.ms { position: relative; display: grid; gap: 6px; }
.ms > span { color: #475467; font-size: 13px; font-weight: 850; }

.ms-box {
  min-height: 42px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
  padding: 5px 8px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
  cursor: text;
}

.ms-box.is-open {
  outline: 3px solid rgba(37, 99, 235, .15);
  border-color: var(--blue);
}

.ms-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 26px;
  padding: 0 6px 0 10px;
  border-radius: 999px;
  background: var(--blue-soft);
  color: var(--blue-dark);
  font-size: 12px;
  font-weight: 950;
}

.ms-x {
  border: 0;
  background: transparent;
  color: var(--blue-dark);
  font-size: 15px;
  line-height: 1;
  cursor: pointer;
  padding: 0 2px;
}

.ms-x:hover { color: var(--danger); }

.ms-input {
  flex: 1;
  min-width: 90px;
  min-height: 28px;
  border: 0;
  padding: 0 2px;
  background: transparent;
  outline: none;
}

.ms-menu {
  position: absolute;
  z-index: 20;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 4px;
  max-height: 240px;
  overflow-y: auto;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
  box-shadow: var(--shadow);
  padding: 4px;
}

.ms-opt {
  display: block;
  width: 100%;
  text-align: left;
  border: 0;
  background: transparent;
  padding: 8px 10px;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
}

.ms-opt.is-active,
.ms-opt:hover {
  background: var(--blue-soft);
  color: var(--blue-dark);
}

.ms-empty { margin: 0; padding: 8px 10px; color: var(--muted); font-size: 13px; }
</style>
