<template>
  <div class="flex flex-col space-y-2">
    <label v-if="label" :for="inputId" class="text-sm font-medium text-gray-700 dark:text-gray-300">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <textarea
      :id="inputId"
      v-model="internalValue"
      :placeholder="placeholder"
      :rows="rows"
      :maxlength="maxlength"
      :required="required"
      :disabled="disabled"
      class="form-control form-input form-input-bordered resize-y"
      @blur="$emit('blur')"
      @focus="$emit('focus')"
    ></textarea>
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
  name: 'TextareaInput',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: ''
    },
    placeholder: {
      type: String,
      default: ''
    },
    rows: {
      type: Number,
      default: 4
    },
    maxlength: {
      type: Number,
      default: null
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
    const inputId = ref(`textarea-input-${Math.random().toString(36).substr(2, 9)}`)
    
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