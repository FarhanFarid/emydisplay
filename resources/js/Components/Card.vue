<template>
  <div
    :class="[bgColor, 'rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300 text-black h-96 flex flex-col']"
  >
    <!-- Title in a box -->
    <div :class="[titleColor, 'rounded-md px-10 py-2 mb-4 mx-auto border border-black-300 shadow-sm']">
      <h1 class="text-2xl font-extrabold text-center">{{ title }}</h1>
    </div>

    <!-- Card content -->
    <div class="flex-1 overflow-y-auto">
      <slot>
        <p>{{ content }}</p>
      </slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  content: {
    type: String,
    default: ''
  },
  bgColor: {
    type: String,
    default: 'bg-white'
  },
  titleColor: {
    type: String,
    default: 'bg-white'
  }
})
</script>
