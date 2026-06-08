<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { ClipboardCheck, Check, X, ChevronDown } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AppSelect from "@/components/AppSelect.vue";
import { useToast } from "@/composables/useToast";
import { useAuthStore } from "@/stores/auth";
import { listCorrections, approveCorrection, rejectCorrection } from "@/api/cms";
import type { AttendanceCorrection, CorrectionStatus } from "@/types";

const toast = useToast();
const auth = useAuthStore();

const corrections = ref<AttendanceCorrection[]>([]);
const loading = ref(false);
const page = ref(1);
const limit = 15;
const total = ref(0);
const totalPages = ref(0);
const filterStatus = ref<string | null>(null);

const rejectModal = ref<{ open: boolean; id: number | null; note: string }>({ open: false, id: null, note: "" });
const acting = ref(false);

const canReview = computed(() => {
  const role = (auth.user?.role ?? "").toLowerCase();
  return ["admin", "hr_admin"].includes(role) || !!auth.user?.hasSupervisees;
});

const statusOptions = [
  { value: null, label: "All Statuses" },
  { value: "pending", label: "Pending" },
  { value: "approved", label: "Approved" },
  { value: "rejected", label: "Rejected" },
];

const statusBadge: Record<CorrectionStatus, string> = {
  pending:  "bg-amber-100 text-amber-700",
  approved: "bg-emerald-100 text-emerald-700",
  rejected: "bg-rose-100 text-rose-700",
};

function formatDateTime(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleString("en-GB", { day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

function formatTime(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" });
}

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short", year: "numeric" });
}

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page: String(page.value), limit: String(limit) });
    if (filterStatus.value) params.set("status", filterStatus.value);
    const res = await listCorrections("?" + params.toString());
    corrections.value = res.data;
    total.value = Number(res.meta?.total ?? 0);
    totalPages.value = Number(res.meta?.totalPages ?? 0);
  } catch (e) {
    toast.error("Failed to load corrections", e instanceof Error ? e.message : "");
  } finally {
    loading.value = false;
  }
}

async function goToPage(p: number) {
  page.value = p;
  await load();
}

async function onFilterChange() {
  page.value = 1;
  await load();
}

async function doApprove(id: number) {
  acting.value = true;
  try {
    await approveCorrection(id);
    toast.success("Correction approved", "Attendance record has been updated.");
    await load();
  } catch (e) {
    toast.error("Failed to approve", e instanceof Error ? e.message : "");
  } finally {
    acting.value = false;
  }
}

function openRejectModal(id: number) {
  rejectModal.value = { open: true, id, note: "" };
}

async function confirmReject() {
  if (!rejectModal.value.id) return;
  acting.value = true;
  try {
    await rejectCorrection(rejectModal.value.id, rejectModal.value.note);
    toast.success("Correction rejected");
    rejectModal.value = { open: false, id: null, note: "" };
    await load();
  } catch (e) {
    toast.error("Failed to reject", e instanceof Error ? e.message : "");
  } finally {
    acting.value = false;
  }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <AppBreadcrumb :items="[{ label: 'Attendance' }, { label: 'Management' }, { label: 'Corrections' }]" />

      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="page-title">Attendance Corrections</h1>
          <p class="mt-0.5 text-sm text-slate-500">Review and action correction requests submitted by staff.</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="flex items-center gap-2">
        <AppSelect
          :model-value="filterStatus"
          :options="statusOptions"
          placeholder="All Statuses"
          @update:model-value="filterStatus = $event; onFilterChange()"
        />
        <span v-if="total > 0" class="text-xs text-slate-400">{{ total }} requests</span>
      </div>

      <!-- Table -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div v-if="loading" class="px-4 py-8 text-center text-sm text-slate-400">Loading…</div>
        <div v-else-if="corrections.length === 0" class="px-4 py-8 text-center text-sm text-slate-400">No correction requests found.</div>
        <div v-else>
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Staff</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Original</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Requested</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Reason</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Submitted</th>
                <th v-if="canReview" class="px-4 py-2"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="c in corrections" :key="c.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2.5 font-medium text-slate-900">{{ c.userName }}</td>
                <td class="px-4 py-2.5 text-slate-600">{{ formatDate(c.workDate) }}</td>
                <!-- Original times -->
                <td class="px-4 py-2.5 text-xs text-slate-500">
                  <div>In: {{ formatTime(c.originalCheckInAt) }}</div>
                  <div>Out: {{ formatTime(c.originalCheckOutAt) }}</div>
                </td>
                <!-- Requested times -->
                <td class="px-4 py-2.5 text-xs">
                  <div v-if="c.correctedCheckInAt" class="text-emerald-700">In: {{ formatTime(c.correctedCheckInAt) }}</div>
                  <div v-if="c.correctedCheckOutAt" class="text-emerald-700">Out: {{ formatTime(c.correctedCheckOutAt) }}</div>
                </td>
                <td class="max-w-[200px] px-4 py-2.5 text-xs text-slate-600">
                  <p class="line-clamp-2">{{ c.reason }}</p>
                  <p v-if="c.rejectionNote" class="mt-0.5 text-rose-500">Note: {{ c.rejectionNote }}</p>
                </td>
                <td class="px-4 py-2.5">
                  <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize', statusBadge[c.status]]">
                    {{ c.status }}
                  </span>
                  <p v-if="c.reviewedByName" class="mt-0.5 text-[11px] text-slate-400">by {{ c.reviewedByName }}</p>
                </td>
                <td class="px-4 py-2.5 text-xs text-slate-400">{{ formatDateTime(c.createdAt) }}</td>
                <td v-if="canReview" class="px-4 py-2.5">
                  <div v-if="c.status === 'pending'" class="flex items-center gap-1.5">
                    <button
                      :disabled="acting"
                      class="flex items-center gap-1 rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white transition-colors hover:bg-emerald-700 disabled:opacity-50"
                      @click="doApprove(c.id)"
                    >
                      <Check class="h-3 w-3" />
                      Approve
                    </button>
                    <button
                      :disabled="acting"
                      class="flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-50"
                      @click="openRejectModal(c.id)"
                    >
                      <X class="h-3 w-3" />
                      Reject
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-2.5">
            <span class="text-xs text-slate-400">Page {{ page }} of {{ totalPages }}</span>
            <div class="flex items-center gap-1">
              <button
                :disabled="page <= 1"
                class="rounded border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40"
                @click="goToPage(page - 1)"
              >Prev</button>
              <button
                :disabled="page >= totalPages"
                class="rounded border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40"
                @click="goToPage(page + 1)"
              >Next</button>
            </div>
          </div>
        </div>
      </article>
    </div>

    <!-- Reject modal -->
    <Teleport to="body">
      <div
        v-if="rejectModal.open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        @click.self="rejectModal.open = false"
      >
        <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white shadow-xl">
          <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
            <X class="h-4 w-4 text-rose-500" />
            <h2 class="text-sm font-semibold text-slate-900">Reject Correction</h2>
          </div>
          <div class="space-y-4 px-5 py-5">
            <div class="space-y-1.5">
              <label class="text-xs font-medium text-slate-600">Reason for rejection <span class="text-slate-400">(optional)</span></label>
              <textarea
                v-model="rejectModal.note"
                rows="3"
                placeholder="Briefly explain why this correction is rejected…"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>
            <div class="flex gap-2 pt-1">
              <button
                class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                @click="rejectModal.open = false"
              >Cancel</button>
              <button
                :disabled="acting"
                class="flex-1 rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-rose-700 disabled:opacity-50"
                @click="confirmReject"
              >Reject</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>
