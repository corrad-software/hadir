<script setup lang="ts">
import { ChevronRight } from "lucide-vue-next";

export type BreadcrumbItem = {
  label: string;
  to?: string;
};

defineProps<{ items: BreadcrumbItem[] }>();
</script>

<template>
  <nav class="flex items-center gap-1.5 text-sm text-slate-400">
    <template v-for="(item, i) in items" :key="i">
      <ChevronRight v-if="i > 0" class="h-3.5 w-3.5 shrink-0" />
      <router-link
        v-if="item.to"
        :to="item.to"
        class="hover:text-slate-600 transition-colors"
      >{{ item.label }}</router-link>
      <span v-else class="font-medium text-slate-700">{{ item.label }}</span>
    </template>
  </nav>
</template>
