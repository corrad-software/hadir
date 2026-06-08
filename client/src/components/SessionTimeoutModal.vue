<script setup lang="ts">
import { Clock } from "lucide-vue-next";
import { useSessionTimeout } from "@/composables/useSessionTimeout";

const { showWarning, countdown, staySignedIn, forceLogout } = useSessionTimeout();

function formatCountdown(s: number): string {
  const m = Math.floor(s / 60);
  const sec = s % 60;
  return `${m}:${String(sec).padStart(2, "0")}`;
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="showWarning"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4"
    >
      <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
          <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100">
            <Clock class="h-4 w-4 text-amber-600" />
          </div>
          <h2 class="text-sm font-semibold text-slate-900">Session about to expire</h2>
        </div>
        <div class="px-5 py-5 text-center">
          <p class="text-sm text-slate-600">You've been inactive for a while. You'll be signed out in:</p>
          <p class="my-4 text-4xl font-bold tabular-nums text-slate-900">{{ formatCountdown(countdown) }}</p>
          <p class="text-xs text-slate-400">Move your mouse or press any key to stay signed in.</p>
        </div>
        <div class="flex gap-2 border-t border-slate-100 px-5 py-4">
          <button
            class="flex-1 rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
            @click="forceLogout"
          >
            Sign out now
          </button>
          <button
            class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-800"
            @click="staySignedIn"
          >
            Stay signed in
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
