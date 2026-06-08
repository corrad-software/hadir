<script setup lang="ts">
import { ref, onMounted } from "vue";
import { ClipboardCheck, CheckCircle2, XCircle, Clock, Search } from "lucide-vue-next";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { useToast } from "@/composables/useToast";
import { listAllAttendance, approveAttendance, rejectAttendance } from "@/api/cms";
import type { AttendanceLog } from "@/types";

const toast = useToast();

const logs = ref<AttendanceLog[]>([]);
const loading = ref(false);
const actionId = ref<number | null>(null);
const rejectingId = ref<number | null>(null);
const rejectReason = ref("");
const page = ref(1);
const total = ref(0);
const totalPages = ref(1);
const LIMIT = 20;

// Filters
const filterFrom = ref(defaultFrom());
const filterTo = ref(todayStr());
const showAllMode = ref(false);

function defaultFrom(): string {
  const d = new Date();
  d.setDate(d.getDate() - 7);
  return d.toISOString().slice(0, 10);
}

function todayStr(): string {
  return new Date().toISOString().slice(0, 10);
}

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    params.set("page", String(page.value));
    params.set("limit", String(LIMIT));
    params.set("approval_status", "pending_approval");
    if (filterFrom.value) params.set("from", filterFrom.value);
    if (filterTo.value) params.set("to", filterTo.value);
    if (!showAllMode.value) {
      params.set("my_subordinates", "1");
    }

    const res = await listAllAttendance(`?${params}`);
    logs.value = res.data;
    total.value = Number(res.meta?.total ?? 0);
    totalPages.value = Number(res.meta?.totalPages ?? 1);
  } catch (e) {
    toast.error("Failed to load approvals", e instanceof Error ? e.message : "");
  } finally {
    loading.value = false;
  }
}

async function approve(id: number) {
  actionId.value = id;
  try {
    await approveAttendance(id);
    toast.success("Approved");
    await load();
  } catch (e) {
    toast.error("Approval failed", e instanceof Error ? e.message : "");
  } finally {
    actionId.value = null;
  }
}

function openReject(id: number) {
  rejectingId.value = id;
  rejectReason.value = "";
}

function cancelReject() {
  rejectingId.value = null;
  rejectReason.value = "";
}

async function confirmReject(id: number) {
  if (!rejectReason.value.trim()) {
    toast.error("Reason required", "Please provide a rejection reason");
    return;
  }
  actionId.value = id;
  try {
    await rejectAttendance(id, rejectReason.value);
    toast.success("Rejected");
    rejectingId.value = null;
    await load();
  } catch (e) {
    toast.error("Rejection failed", e instanceof Error ? e.message : "");
  } finally {
    actionId.value = null;
  }
}

function prevPage() {
  if (page.value > 1) { page.value--; load(); }
}

function nextPage() {
  if (page.value < totalPages.value) { page.value++; load(); }
}

function onSearch() {
  page.value = 1;
  load();
}

function statusLabel(log: AttendanceLog): string {
  if (log.status === "late") return "Late check-in";
  if (log.status === "early_leave") return "Early checkout";
  return log.status;
}

function formatTime(iso: string | null): string {
  if (!iso) return "—";
  return new Date(iso).toLocaleTimeString(undefined, { hour: "2-digit", minute: "2-digit" });
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <AppBreadcrumb :items="[{ label: 'Attendance' }, { label: 'Approvals' }]" />

      <!-- Header -->
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="page-title">Attendance Approvals</h1>
          <p class="mt-0.5 text-sm text-slate-500">Review and approve or reject pending attendance submissions from staff.</p>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input v-model="showAllMode" type="checkbox" class="rounded" @change="onSearch" />
          Show all divisions
        </label>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap gap-3">
        <div class="flex items-center gap-2">
          <label class="text-sm text-slate-500">From</label>
          <input v-model="filterFrom" type="date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none" />
        </div>
        <div class="flex items-center gap-2">
          <label class="text-sm text-slate-500">To</label>
          <input v-model="filterTo" type="date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none" />
        </div>
        <button
          class="flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50"
          @click="onSearch"
        >
          <Search class="h-4 w-4" />
          Apply
        </button>
      </div>

      <!-- List -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
          <ClipboardCheck class="h-4 w-4 text-amber-600" />
          <h2 class="text-sm font-semibold text-slate-900">
            Pending Approvals
            <span v-if="total > 0" class="ml-1.5 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">{{ total }}</span>
          </h2>
        </div>

        <div v-if="loading" class="px-4 py-8 text-center text-sm text-slate-400">Loading…</div>
        <div v-else-if="logs.length === 0" class="flex flex-col items-center gap-2 px-4 py-10 text-center">
          <CheckCircle2 class="h-8 w-8 text-emerald-300" />
          <p class="text-sm text-slate-400">No pending approvals.</p>
        </div>

        <div v-else class="divide-y divide-slate-100">
          <div v-for="log in logs" :key="log.id" class="px-4 py-3">
            <!-- Main row -->
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-medium text-slate-900">{{ log.user?.name ?? `User #${log.userId}` }}</p>
                  <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 flex items-center gap-1">
                    <Clock class="h-3 w-3" />
                    {{ statusLabel(log) }}
                  </span>
                </div>
                <p class="mt-0.5 text-xs text-slate-400">
                  {{ log.workDate }}
                  · In: {{ formatTime(log.checkInAt) }}
                  · Out: {{ formatTime(log.checkOutAt) }}
                </p>
              </div>

              <div v-if="rejectingId !== log.id" class="flex shrink-0 items-center gap-2">
                <button
                  class="flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                  :disabled="actionId === log.id"
                  @click="approve(log.id)"
                >
                  <CheckCircle2 class="h-3.5 w-3.5" />
                  Approve
                </button>
                <button
                  class="flex items-center gap-1.5 rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 disabled:opacity-50"
                  :disabled="actionId === log.id"
                  @click="openReject(log.id)"
                >
                  <XCircle class="h-3.5 w-3.5" />
                  Reject
                </button>
              </div>
            </div>

            <!-- Reject form -->
            <div v-if="rejectingId === log.id" class="mt-3 space-y-2 rounded-lg border border-rose-200 bg-rose-50/40 p-3">
              <label class="text-xs font-medium text-rose-700">Rejection reason (required)</label>
              <textarea
                v-model="rejectReason"
                rows="2"
                maxlength="500"
                class="w-full rounded-lg border border-rose-300 px-3 py-2 text-sm focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                placeholder="Enter reason…"
              />
              <div class="flex gap-2">
                <button
                  class="rounded-lg bg-rose-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-rose-700 disabled:opacity-50"
                  :disabled="!rejectReason.trim() || actionId === log.id"
                  @click="confirmReject(log.id)"
                >
                  Confirm Reject
                </button>
                <button
                  class="rounded-lg border border-slate-300 px-4 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                  @click="cancelReject"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-2.5">
          <span class="text-xs text-slate-400">Page {{ page }} of {{ totalPages }}</span>
          <div class="flex gap-2">
            <button class="rounded border border-slate-300 px-3 py-1 text-xs text-slate-600 hover:bg-slate-50 disabled:opacity-40" :disabled="page <= 1" @click="prevPage">Prev</button>
            <button class="rounded border border-slate-300 px-3 py-1 text-xs text-slate-600 hover:bg-slate-50 disabled:opacity-40" :disabled="page >= totalPages" @click="nextPage">Next</button>
          </div>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
