<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from "vue";
import { ChevronDown, X, Search, Check } from "lucide-vue-next";

export type SelectOption = {
  value: string | number | null;
  label: string;
};

const props = withDefaults(defineProps<{
  modelValue: string | number | null;
  options: SelectOption[];
  placeholder?: string;
  clearable?: boolean;
}>(), {
  placeholder: "Select…",
  clearable: true,
});

const emit = defineEmits<{
  "update:modelValue": [value: string | number | null];
}>();

const open = ref(false);
const search = ref("");
const containerRef = ref<HTMLElement | null>(null);
const searchRef = ref<HTMLInputElement | null>(null);

const selectedLabel = computed(() => {
  const opt = props.options.find((o) => o.value === props.modelValue);
  return opt?.label ?? "";
});

const filtered = computed(() => {
  const q = search.value.toLowerCase().trim();
  if (!q) return props.options;
  return props.options.filter((o) => o.label.toLowerCase().includes(q));
});

function select(value: string | number | null) {
  emit("update:modelValue", value);
  open.value = false;
  search.value = "";
}

async function toggle() {
  open.value = !open.value;
  if (open.value) {
    search.value = "";
    await nextTick();
    searchRef.value?.focus();
  }
}

function clear(e: MouseEvent) {
  e.stopPropagation();
  emit("update:modelValue", null);
}

function onClickOutside(e: MouseEvent) {
  if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
    open.value = false;
    search.value = "";
  }
}

onMounted(() => document.addEventListener("mousedown", onClickOutside));
onUnmounted(() => document.removeEventListener("mousedown", onClickOutside));
</script>

<template>
  <div ref="containerRef" class="relative w-full">
    <!-- Trigger -->
    <button
      type="button"
      class="flex h-8 w-full items-center justify-between gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 text-sm shadow-sm transition-colors hover:border-slate-400 focus:outline-none"
      :class="open ? 'border-slate-400 ring-2 ring-slate-200' : ''"
      @click="toggle"
    >
      <span class="truncate leading-none" :class="modelValue === null ? 'text-slate-400' : 'text-slate-800'">
        {{ modelValue !== null ? selectedLabel : placeholder }}
      </span>
      <span class="flex shrink-0 items-center gap-0.5 text-slate-400">
        <X
          v-if="clearable && modelValue !== null"
          class="h-2.5 w-2.5 rounded hover:text-slate-600"
          @click.stop="clear($event)"
        />
        <ChevronDown class="h-3 w-3 transition-transform duration-150" :class="open ? 'rotate-180' : ''" />
      </span>
    </button>

    <!-- Dropdown -->
    <div
      v-if="open"
      class="absolute left-0 top-full z-[200] mt-1 min-w-full rounded-lg border border-slate-200 bg-white shadow-lg"
    >
      <!-- Search -->
      <div class="border-b border-slate-100 p-1.5">
        <div class="flex items-center gap-1 rounded-md border border-slate-200 bg-slate-50 px-1.5 py-1">
          <Search class="h-3 w-3 shrink-0 text-slate-400" />
          <input
            ref="searchRef"
            v-model="search"
            type="text"
            class="w-full bg-transparent text-sm leading-none outline-none placeholder:text-slate-400"
            placeholder="Search…"
          />
        </div>
      </div>

      <!-- Options -->
      <ul class="max-h-44 overflow-y-auto py-0.5">
        <li v-if="clearable">
          <button
            type="button"
            class="flex w-full items-center justify-between px-2.5 py-1.5 text-left text-sm text-slate-400 hover:bg-slate-50"
            @click="select(null)"
          >
            <span class="italic">{{ placeholder }}</span>
            <Check v-if="modelValue === null" class="h-3 w-3 shrink-0 text-violet-600" />
          </button>
        </li>

        <li v-for="opt in filtered" :key="String(opt.value)">
          <button
            type="button"
            class="flex w-full items-center justify-between px-2.5 py-1.5 text-left text-sm transition-colors"
            :class="opt.value === modelValue ? 'bg-violet-50 text-violet-700' : 'text-slate-800 hover:bg-slate-50'"
            @click="select(opt.value)"
          >
            <span class="truncate">{{ opt.label }}</span>
            <Check v-if="opt.value === modelValue" class="ml-2 h-3 w-3 shrink-0 text-violet-600" />
          </button>
        </li>

        <li v-if="filtered.length === 0" class="px-2.5 py-3 text-center text-sm text-slate-400">
          No results
        </li>
      </ul>
    </div>
  </div>
</template>
