<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { BarChart2, Download, Filter } from "lucide-vue-next";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { useToast } from "@/composables/useToast";
import { getAttendanceReport } from "@/api/cms";
import type { AttendanceReportRow } from "@/types";

const toast = useToast();
const rows = ref<AttendanceReportRow[]>([]);
const loading = ref(false);

const today = new Date();
const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0, 10);
const todayStr = today.toISOString().slice(0, 10);

const from = ref(firstOfMonth);
const to = ref(todayStr);

const maxTotal = computed(() => Math.max(...rows.value.map((r) => r.total), 1));

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString("id-ID", { weekday: "short", day: "numeric", month: "short" });
}

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams({ from: from.value, to: to.value });
    const res = await getAttendanceReport("?" + params.toString());
    rows.value = res.data;
  } catch (e) {
    toast.error("Failed to load report", e instanceof Error ? e.message : "");
  } finally {
    loading.value = false;
  }
}

const totals = computed(() => ({
  onTime: rows.value.reduce((s, r) => s + r.onTime, 0),
  late: rows.value.reduce((s, r) => s + r.late, 0),
  earlyLeave: rows.value.reduce((s, r) => s + r.earlyLeave, 0),
  absent: rows.value.reduce((s, r) => s + r.absent, 0),
  total: rows.value.reduce((s, r) => s + r.total, 0),
}));

function exportCsv() {
  const header = ["Date", "On Time", "Late", "Early Leave", "Absent", "Total"];
  const dataRows = rows.value.map((r) => [
    r.workDate,
    r.onTime,
    r.late,
    r.earlyLeave,
    r.absent,
    r.total,
  ]);
  const totalRow = ["TOTAL", totals.value.onTime, totals.value.late, totals.value.earlyLeave, totals.value.absent, totals.value.total];
  const csv = [header, ...dataRows, totalRow].map((row) => row.join(",")).join("\n");

  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `attendance-report-${from.value}-to-${to.value}.csv`;
  a.click();
  URL.revokeObjectURL(url);
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <AppBreadcrumb :items="[{ label: 'Attendance' }, { label: 'Management' }, { label: 'Reports' }]" />

      <!-- Header -->
      <div class="flex items-start">
        <div>
          <h1 class="page-title">Attendance Report</h1>
          <p class="mt-0.5 text-sm text-slate-500">Monthly summary of team attendance broken down by status.</p>
        </div>
      </div>

      <!-- Date range -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
          <Filter class="h-4 w-4 text-slate-500" />
          <h2 class="text-sm font-semibold text-slate-900">Date Range</h2>
        </div>
        <div class="p-4">
          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-1.5">
              <label class="text-xs font-medium text-slate-500">From</label>
              <input v-model="from" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-medium text-slate-500">To</label>
              <input v-model="to" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
            </div>
          </div>
          <div class="mt-3 flex items-center gap-2">
            <button class="rounded-lg bg-slate-900 px-4 py-1.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800" @click="load">Generate</button>
            <button
              v-if="rows.length > 0"
              class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
              @click="exportCsv"
            >
              <Download class="h-3.5 w-3.5" />
              Export CSV
            </button>
          </div>
        </div>
      </article>

      <!-- Summary -->
      <div v-if="rows.length > 0" class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm text-center">
          <p class="text-2xl font-semibold text-emerald-600">{{ totals.onTime }}</p>
          <p class="mt-1 text-xs text-slate-400">On Time</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm text-center">
          <p class="text-2xl font-semibold text-amber-600">{{ totals.late }}</p>
          <p class="mt-1 text-xs text-slate-400">Late</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm text-center">
          <p class="text-2xl font-semibold text-orange-600">{{ totals.earlyLeave }}</p>
          <p class="mt-1 text-xs text-slate-400">Early Leave</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm text-center">
          <p class="text-2xl font-semibold text-rose-600">{{ totals.absent }}</p>
          <p class="mt-1 text-xs text-slate-400">Absent</p>
        </article>
      </div>

      <!-- Chart + Table -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
          <BarChart2 class="h-4 w-4 text-blue-600" />
          <h2 class="text-sm font-semibold text-slate-900">Daily Breakdown</h2>
        </div>

        <div v-if="loading" class="px-4 py-8 text-center text-sm text-slate-400">Loading…</div>
        <div v-else-if="rows.length === 0" class="px-4 py-8 text-center text-sm text-slate-400">No data for selected range.</div>
        <div v-else>
          <!-- Bar chart -->
          <div class="border-b border-slate-100 p-4">
            <div class="space-y-2">
              <div v-for="row in rows" :key="row.workDate" class="flex items-center gap-3">
                <span class="w-24 shrink-0 text-right text-xs text-slate-400">{{ formatDate(row.workDate) }}</span>
                <div class="flex h-4 flex-1 gap-px overflow-hidden rounded-full bg-slate-100">
                  <div v-if="row.onTime" :style="{ width: (row.onTime / maxTotal * 100) + '%' }" class="bg-emerald-400"></div>
                  <div v-if="row.late" :style="{ width: (row.late / maxTotal * 100) + '%' }" class="bg-amber-400"></div>
                  <div v-if="row.earlyLeave" :style="{ width: (row.earlyLeave / maxTotal * 100) + '%' }" class="bg-orange-400"></div>
                  <div v-if="row.absent" :style="{ width: (row.absent / maxTotal * 100) + '%' }" class="bg-rose-400"></div>
                </div>
                <span class="w-5 text-right text-xs text-slate-400">{{ row.total }}</span>
              </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-400">
              <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-3 rounded-sm bg-emerald-400"></span>On Time</span>
              <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-3 rounded-sm bg-amber-400"></span>Late</span>
              <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-3 rounded-sm bg-orange-400"></span>Early Leave</span>
              <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-3 rounded-sm bg-rose-400"></span>Absent</span>
            </div>
          </div>

          <!-- Detail table -->
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-emerald-600">On Time</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-amber-600">Late</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-orange-600">Early Leave</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-rose-600">Absent</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="row in rows" :key="row.workDate" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-medium text-slate-900">{{ formatDate(row.workDate) }}</td>
                <td class="px-4 py-2 text-right text-emerald-600">{{ row.onTime || '—' }}</td>
                <td class="px-4 py-2 text-right text-amber-600">{{ row.late || '—' }}</td>
                <td class="px-4 py-2 text-right text-orange-600">{{ row.earlyLeave || '—' }}</td>
                <td class="px-4 py-2 text-right text-rose-600">{{ row.absent || '—' }}</td>
                <td class="px-4 py-2 text-right font-semibold text-slate-900">{{ row.total }}</td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-slate-200">
              <tr class="bg-slate-50">
                <td class="px-4 py-2 text-xs font-semibold text-slate-700">Total</td>
                <td class="px-4 py-2 text-right text-xs font-semibold text-emerald-600">{{ totals.onTime }}</td>
                <td class="px-4 py-2 text-right text-xs font-semibold text-amber-600">{{ totals.late }}</td>
                <td class="px-4 py-2 text-right text-xs font-semibold text-orange-600">{{ totals.earlyLeave }}</td>
                <td class="px-4 py-2 text-right text-xs font-semibold text-rose-600">{{ totals.absent }}</td>
                <td class="px-4 py-2 text-right text-xs font-semibold text-slate-900">{{ totals.total }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
