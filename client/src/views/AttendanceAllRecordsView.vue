<script setup lang="ts">
import { ref, onMounted } from "vue";
import { Users, Filter, Pencil, Save, X } from "lucide-vue-next";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { useToast } from "@/composables/useToast";
import { listAllAttendance, updateAttendanceLog } from "@/api/cms";
import type { AttendanceLog, AttendanceStatus } from "@/types";

const toast = useToast();
const logs = ref<AttendanceLog[]>([]);
const page = ref(1);
const total = ref(0);
const limit = 20;
const loading = ref(false);

const filterUserId = ref("");
const filterFrom = ref("");
const filterTo = ref("");
const filterStatus = ref("");

const editLog = ref<AttendanceLog | null>(null);
const editStatus = ref<AttendanceStatus>("on_time");
const editNotes = ref("");
const saving = ref(false);

const statusColors: Record<string, string> = {
  on_time: "bg-emerald-100 text-emerald-700",
  late: "bg-amber-100 text-amber-700",
  early_leave: "bg-orange-100 text-orange-700",
  absent: "bg-rose-100 text-rose-700",
  pending: "bg-slate-100 text-slate-500",
};

const statusLabels: Record<string, string> = {
  on_time: "On Time",
  late: "Late",
  early_leave: "Early Leave",
  absent: "Absent",
  pending: "Pending",
};

const ALL_STATUSES: AttendanceStatus[] = ["on_time", "late", "early_leave", "absent", "pending"];

function formatTime(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
}

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString("id-ID", { weekday: "short", day: "numeric", month: "short", year: "numeric" });
}

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page: String(page.value), limit: String(limit) });
    if (filterUserId.value) params.set("user_id", filterUserId.value);
    if (filterFrom.value) params.set("from", filterFrom.value);
    if (filterTo.value) params.set("to", filterTo.value);
    if (filterStatus.value) params.set("status", filterStatus.value);
    const res = await listAllAttendance("?" + params.toString());
    logs.value = res.data;
    total.value = (res.meta?.total as number) || 0;
  } catch (e) {
    toast.error("Failed to load records", e instanceof Error ? e.message : "");
  } finally {
    loading.value = false;
  }
}

function applyFilter() {
  page.value = 1;
  load();
}

function clearFilter() {
  filterUserId.value = "";
  filterFrom.value = "";
  filterTo.value = "";
  filterStatus.value = "";
  page.value = 1;
  load();
}

function openEdit(log: AttendanceLog) {
  editLog.value = log;
  editStatus.value = log.status;
  editNotes.value = log.notes || "";
}

function closeEdit() {
  editLog.value = null;
}

async function saveEdit() {
  if (!editLog.value) return;
  saving.value = true;
  try {
    await updateAttendanceLog(editLog.value.id, { status: editStatus.value, notes: editNotes.value });
    toast.success("Record updated");
    closeEdit();
    await load();
  } catch (e) {
    toast.error("Save failed", e instanceof Error ? e.message : "");
  } finally {
    saving.value = false;
  }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <AppBreadcrumb :items="[{ label: 'Attendance' }, { label: 'Management' }, { label: 'All Records' }]" />

      <!-- Header -->
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="page-title">All Attendance Records</h1>
          <p class="mt-0.5 text-sm text-slate-500">View, filter, and manage attendance logs for all staff.</p>
        </div>
        <span class="text-sm text-slate-400">{{ total }} records</span>
      </div>

      <!-- Filters -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
          <Filter class="h-4 w-4 text-slate-500" />
          <h2 class="text-sm font-semibold text-slate-900">Filters</h2>
        </div>
        <div class="p-4">
          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-1.5">
              <label class="text-xs font-medium text-slate-500">User ID</label>
              <input v-model="filterUserId" type="number" placeholder="e.g. 5" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-medium text-slate-500">Date From</label>
              <input v-model="filterFrom" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-medium text-slate-500">Date To</label>
              <input v-model="filterTo" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-medium text-slate-500">Status</label>
              <select v-model="filterStatus" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                <option value="">All statuses</option>
                <option v-for="s in ALL_STATUSES" :key="s" :value="s">{{ statusLabels[s] }}</option>
              </select>
            </div>
          </div>
          <div class="mt-3 flex items-center gap-2">
            <button class="rounded-lg bg-slate-900 px-4 py-1.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800" @click="applyFilter">Apply</button>
            <button class="rounded-lg border border-slate-300 px-4 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" @click="clearFilter">Clear</button>
          </div>
        </div>
      </article>

      <!-- Table -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
          <Users class="h-4 w-4 text-blue-600" />
          <h2 class="text-sm font-semibold text-slate-900">Records</h2>
        </div>

        <div v-if="loading" class="px-4 py-8 text-center text-sm text-slate-400">Loading…</div>
        <div v-else-if="logs.length === 0" class="px-4 py-8 text-center text-sm text-slate-400">No records found.</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Employee</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Check In</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Check Out</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Notes</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="log in logs" :key="log.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2">
                  <p class="font-medium text-slate-900">{{ log.user?.name || `User #${log.userId}` }}</p>
                  <p class="text-xs text-slate-400">{{ log.user?.email }}</p>
                </td>
                <td class="px-4 py-2 text-slate-700">{{ formatDate(log.workDate) }}</td>
                <td class="px-4 py-2 text-slate-500">{{ formatTime(log.checkInAt) }}</td>
                <td class="px-4 py-2 text-slate-500">{{ formatTime(log.checkOutAt) }}</td>
                <td class="px-4 py-2">
                  <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', statusColors[log.status] || 'bg-slate-100 text-slate-500']">
                    {{ statusLabels[log.status] || log.status }}
                  </span>
                </td>
                <td class="max-w-[160px] truncate px-4 py-2 text-xs text-slate-400">{{ log.notes || '—' }}</td>
                <td class="px-4 py-2 text-right">
                  <button class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 ml-auto" title="Edit" @click="openEdit(log)">
                    <Pencil class="h-3.5 w-3.5" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="total > limit" class="flex items-center justify-between border-t border-slate-100 px-4 py-2.5">
          <span class="text-xs text-slate-400">{{ (page - 1) * limit + 1 }}–{{ Math.min(page * limit, total) }} of {{ total }}</span>
          <div class="flex gap-2">
            <button :disabled="page === 1" class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40" @click="page--; load()">Prev</button>
            <button :disabled="page * limit >= total" class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40" @click="page++; load()">Next</button>
          </div>
        </div>
      </article>
    </div>

    <!-- Edit Modal -->
    <div v-if="editLog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @mousedown.self="closeEdit">
      <article class="w-full max-w-md rounded-lg border border-slate-200 bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
          <div class="flex items-center gap-2">
            <Pencil class="h-4 w-4 text-violet-600" />
            <h2 class="text-sm font-semibold text-slate-900">Edit Record</h2>
          </div>
          <button class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="closeEdit">
            <X class="h-4 w-4" />
          </button>
        </div>
        <div class="space-y-3 p-4">
          <p class="text-xs text-slate-400">{{ editLog.user?.name }} · {{ formatDate(editLog.workDate) }}</p>
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Status</label>
            <select v-model="editStatus" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
              <option v-for="s in ALL_STATUSES" :key="s" :value="s">{{ statusLabels[s] }}</option>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Notes</label>
            <textarea v-model="editNotes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="HR override notes…"></textarea>
          </div>
          <div class="flex items-center gap-3 border-t border-slate-100 pt-3">
            <button
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-50"
              :disabled="saving"
              @click="saveEdit"
            >
              <Save class="h-4 w-4" />
              {{ saving ? 'Saving…' : 'Update' }}
            </button>
            <button class="flex items-center gap-2 rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" @click="closeEdit">
              <X class="h-4 w-4" />
              Cancel
            </button>
          </div>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
