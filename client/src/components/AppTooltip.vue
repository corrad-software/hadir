<script setup lang="ts">
defineProps<{ text: string }>();
</script>

<template>
  <div class="relative inline-flex group">
    <slot />
    <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100">
      {{ text }}
      <span class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-slate-800" />
    </div>
  </div>
</template>
