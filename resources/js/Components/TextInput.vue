<script setup>
defineProps({
  label: String,
  modelValue: [String, Number],
  type: { type: String, default: 'text' },
  required: Boolean,
  name: String,
  autocomplete: String,
  inputmode: String,
  maxlength: [String, Number],
  error: String,
});
defineEmits(['update:modelValue']);
</script>

<template>
  <label>
    {{ label }}
    <input
      :type="type"
      :name="name"
      :required="required"
      :autocomplete="autocomplete"
      :inputmode="inputmode"
      :maxlength="maxlength"
      :aria-invalid="error ? 'true' : 'false'"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <small v-if="error" class="field-error">{{ error }}</small>
  </label>
</template>
