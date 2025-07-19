<template>
  <div class="flex items-center space-x-3">
    <input
      :id="inputId"
      v-model="internalValue"
      type="checkbox"
      :required="required"
      :disabled="disabled"
      class="form-checkbox h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
      @blur="$emit('blur')"
      @focus="$emit('focus')"
    />
    <label v-if="label" :for="inputId" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <div v-if="error" class="text-sm text-red-500">
      {{ error }}
    </div>
    <div v-if="help" class="text-sm text-gray-500 dark:text-gray-400">
      {{ help }}
    </div>
  </div>
</template>

<script>
import { computed, ref } from 'vue'

export default {
  name: 'CheckboxInput',
  props: {
    modelValue: {
      type: Boolean,
      default: false
    },
    label: {
      type: String,
      default: ''
    },
    required: {
      type: Boolean,
      default: false
    },
    disabled: {
      type: Boolean,
      default: false
    },
    error: {
      type: String,
      default: ''
    },
    help: {
      type: String,
      default: ''
    }
  },
  emits: ['update:modelValue', 'blur', 'focus'],
  setup(props, { emit }) {
    const inputId = ref(`checkbox-input-${Math.random().toString(36).substr(2, 9)}`)
    
    const internalValue = computed({
      get: () => props.modelValue,
      set: (value) => emit('update:modelValue', value)
    })

    return {
      inputId,
      internalValue
    }
  }
}
</script>