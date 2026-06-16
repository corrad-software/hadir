<script setup lang="ts">
import { Navigation, MapPinOff } from "lucide-vue-next";

defineProps<{
  open: boolean;
  action: "checkin" | "checkout" | null;
  acquiring: boolean;
  latitude: number | null;
  longitude: number | null;
  error: string | null;
}>();

defineEmits<{
  cancel: [];
  confirm: [];
  retry: [];
}>();
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
      @click.self="$emit('cancel')"
    >
      <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white shadow-xl">
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
          <Navigation class="h-4 w-4 text-blue-600" />
          <h2 class="text-sm font-semibold text-slate-900">
            Confirm Location — {{ action === "checkin" ? "Check In" : "Check Out" }}
          </h2>
        </div>

        <div v-if="acquiring" class="flex flex-col items-center gap-3 px-5 py-8 text-center">
          <div class="h-8 w-8 animate-spin rounded-full border-2 border-slate-200 border-t-blue-600" />
          <p class="text-sm text-slate-500">Acquiring your location…</p>
          <p class="text-xs text-slate-400">Allow location access if your browser asks.</p>
        </div>

        <div v-else-if="error" class="space-y-4 px-5 py-5">
          <div class="flex items-start gap-3 rounded-lg border border-rose-100 bg-rose-50 px-4 py-3">
            <MapPinOff class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" />
            <div class="space-y-1">
              <p class="text-sm font-medium text-rose-800">GPS required for attendance</p>
              <p class="text-xs leading-relaxed text-rose-700">{{ error }}</p>
            </div>
          </div>
          <div class="flex gap-2 pt-1">
            <button
              class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
              @click="$emit('cancel')"
            >
              Cancel
            </button>
            <button
              class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-800"
              @click="$emit('retry')"
            >
              Try Again
            </button>
          </div>
        </div>

        <div v-else class="space-y-4 px-5 py-5">
          <p class="text-sm text-slate-600">Your current GPS coordinates will be recorded. Confirm to proceed.</p>
          <div class="space-y-1 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
            <div class="flex items-center justify-between text-xs">
              <span class="font-medium text-slate-500">Latitude</span>
              <span class="font-mono text-slate-800">{{ latitude?.toFixed(6) }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="font-medium text-slate-500">Longitude</span>
              <span class="font-mono text-slate-800">{{ longitude?.toFixed(6) }}</span>
            </div>
          </div>
          <div class="flex gap-2 pt-1">
            <button
              class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
              @click="$emit('cancel')"
            >
              Cancel
            </button>
            <button
              class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-800"
              @click="$emit('confirm')"
            >
              {{ action === "checkin" ? "Check In" : "Check Out" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
