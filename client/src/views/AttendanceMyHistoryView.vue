<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { History, ChevronLeft, ChevronRight, MapPin, MapPinOff, FilePenLine } from "lucide-vue-next";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { useToast } from "@/composables/useToast";
import { listMyAttendance, requestCorrection } from "@/api/cms";
import type { AttendanceLog } from "@/types";

const toast = useToast();
const loading = ref(false);
const logMap = ref<Record<string, AttendanceLog>>({});
const selected = ref<AttendanceLog | null>(null);
const mapTab = ref<"checkin" | "checkout">("checkin");

function osmEmbedUrl(lat: number, lng: number) {
  const d = 0.004;
  return `https://www.openstreetmap.org/export/embed.html?bbox=${lng - d},${lat - d},${lng + d},${lat + d}&layer=mapnik&marker=${lat},${lng}`;
}

function osmOpenUrl(lat: number, lng: number) {
  return `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}#map=17/${lat}/${lng}`;
}

watch(selected, (log) => {
  if (!log) return;
  mapTab.value = log.checkInLatitude !== null ? "checkin" : "checkout";
});

const today = new Date();
const curYear = ref(today.getFullYear());
const curMonth = ref(today.getMonth());

const MONTH_NAMES = ["January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December"];
const DAY_NAMES = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

const statusColors: Record<string, string> = {
  on_time: "bg-emerald-100 text-emerald-700 hover:bg-emerald-200",
  late: "bg-amber-100 text-amber-700 hover:bg-amber-200",
  early_leave: "bg-orange-100 text-orange-700 hover:bg-orange-200",
  absent: "bg-rose-100 text-rose-700 hover:bg-rose-200",
  pending: "bg-slate-100 text-slate-500 hover:bg-slate-200",
};

const statusDot: Record<string, string> = {
  on_time: "bg-emerald-400",
  late: "bg-amber-400",
  early_leave: "bg-orange-400",
  absent: "bg-rose-400",
  pending: "bg-slate-300",
};

const statusLabels: Record<string, string> = {
  on_time: "On Time",
  late: "Late",
  early_leave: "Early Leave",
  absent: "Absent",
  pending: "Pending",
};

const calendarDays = computed(() => {
  const year = curYear.value;
  const month = curMonth.value;
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const cells: Array<{ date: string; day: number } | null> = [];

  for (let i = 0; i < firstDay; i++) cells.push(null);
  for (let d = 1; d <= daysInMonth; d++) {
    const mm = String(month + 1).padStart(2, "0");
    const dd = String(d).padStart(2, "0");
    cells.push({ date: `${year}-${mm}-${dd}`, day: d });
  }

  return cells;
});

function isToday(dateStr: string) {
  return dateStr === today.toISOString().slice(0, 10);
}

function prevMonth() {
  if (curMonth.value === 0) { curYear.value--; curMonth.value = 11; }
  else curMonth.value--;
  load();
}

function nextMonth() {
  if (curMonth.value === 11) { curYear.value++; curMonth.value = 0; }
  else curMonth.value++;
  load();
}

function formatTime(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" });
}

async function load() {
  loading.value = true;
  selected.value = null;
  try {
    const year = curYear.value;
    const month = curMonth.value;
    const from = `${year}-${String(month + 1).padStart(2, "0")}-01`;
    const lastDay = new Date(year, month + 1, 0).getDate();
    const to = `${year}-${String(month + 1).padStart(2, "0")}-${lastDay}`;

    const res = await listMyAttendance(`?from=${from}&to=${to}&limit=31`);
    const map: Record<string, AttendanceLog> = {};
    for (const log of res.data) map[log.workDate] = log;
    logMap.value = map;
  } catch (e) {
    toast.error("Failed to load records", e instanceof Error ? e.message : "");
  } finally {
    loading.value = false;
  }
}

const correctionModal = ref<{
  open: boolean;
  log: AttendanceLog | null;
  checkInAt: string;
  checkOutAt: string;
  reason: string;
  submitting: boolean;
}>({ open: false, log: null, checkInAt: "", checkOutAt: "", reason: "", submitting: false });

function openCorrectionModal(log: AttendanceLog) {
  correctionModal.value = {
    open: true,
    log,
    checkInAt: log.checkInAt ? new Date(log.checkInAt).toISOString().slice(0, 16) : "",
    checkOutAt: log.checkOutAt ? new Date(log.checkOutAt).toISOString().slice(0, 16) : "",
    reason: "",
    submitting: false,
  };
}

async function submitCorrection() {
  const { log, checkInAt, checkOutAt, reason } = correctionModal.value;
  if (!log) return;
  if (!checkInAt && !checkOutAt) {
    toast.error("Validation", "Please provide at least one corrected time.");
    return;
  }
  if (reason.trim().length < 10) {
    toast.error("Validation", "Reason must be at least 10 characters.");
    return;
  }
  correctionModal.value.submitting = true;
  try {
    await requestCorrection({
      attendanceLogId: log.id,
      correctedCheckInAt: checkInAt || null,
      correctedCheckOutAt: checkOutAt || null,
      reason: reason.trim(),
    });
    toast.success("Correction submitted", "Your request has been sent for review.");
    correctionModal.value.open = false;
  } catch (e) {
    toast.error("Failed to submit", e instanceof Error ? e.message : "");
  } finally {
    correctionModal.value.submitting = false;
  }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <AppBreadcrumb :items="[{ label: 'Attendance' }, { label: 'My History' }]" />

      <!-- Header -->
      <div class="flex items-start">
        <div>
          <h1 class="page-title">My Attendance History</h1>
          <p class="mt-0.5 text-sm text-slate-500">Your personal attendance record, browsable by month.</p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-[1fr_300px]">
        <!-- Left column: calendar + detail + map -->
        <div class="space-y-4">
          <!-- Calendar -->
          <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
              <div class="flex items-center gap-2">
                <History class="h-4 w-4 text-violet-600" />
                <h2 class="text-sm font-semibold text-slate-900">
                  {{ MONTH_NAMES[curMonth] }} {{ curYear }}
                </h2>
              </div>
              <div class="flex items-center gap-1">
                <button class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700" @click="prevMonth">
                  <ChevronLeft class="h-4 w-4" />
                </button>
                <button class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700" @click="nextMonth">
                  <ChevronRight class="h-4 w-4" />
                </button>
              </div>
            </div>

            <div v-if="loading" class="px-4 py-8 text-center text-sm text-slate-400">Loading…</div>
            <div v-else class="p-3">
              <div class="mb-1 grid grid-cols-7">
                <div
                  v-for="d in DAY_NAMES"
                  :key="d"
                  class="py-1 text-center text-xs font-semibold uppercase tracking-wider text-slate-400"
                >
                  {{ d }}
                </div>
              </div>

              <div class="grid grid-cols-7 gap-1">
                <div v-for="(cell, idx) in calendarDays" :key="idx">
                  <div v-if="!cell" class="h-14"></div>

                  <div
                    v-else-if="!logMap[cell.date]"
                    :class="[
                      'flex h-14 flex-col items-center justify-start rounded-lg pt-1.5',
                      isToday(cell.date) ? 'ring-2 ring-slate-900 ring-offset-1' : '',
                    ]"
                  >
                    <span :class="['text-sm font-medium', isToday(cell.date) ? 'text-slate-900' : 'text-slate-400']">
                      {{ cell.day }}
                    </span>
                  </div>

                  <button
                    v-else
                    :class="[
                      'flex h-14 w-full flex-col items-center justify-start rounded-lg pt-1.5 transition-colors',
                      selected?.workDate === cell.date ? 'ring-2 ring-slate-900 ring-offset-1' : '',
                      statusColors[logMap[cell.date].status] || 'bg-slate-100 hover:bg-slate-200',
                    ]"
                    @click="selected = selected?.workDate === cell.date ? null : logMap[cell.date]"
                  >
                    <span class="text-sm font-semibold">{{ cell.day }}</span>
                    <span class="mt-0.5 text-[10px] font-medium leading-none">
                      {{ statusLabels[logMap[cell.date].status]?.split(' ')[0] }}
                    </span>
                  </button>
                </div>
              </div>

              <div class="mt-3 flex flex-wrap gap-3 border-t border-slate-100 pt-3">
                <span v-for="(dot, key) in statusDot" :key="key" class="flex items-center gap-1.5 text-xs text-slate-500">
                  <span :class="['h-2 w-2 rounded-full', dot]"></span>
                  {{ statusLabels[key] }}
                </span>
              </div>
            </div>
          </article>

          <!-- Detail row (below calendar, full width) -->
          <article v-if="selected" class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
              <span :class="['h-2.5 w-2.5 rounded-full', statusDot[selected.status] || 'bg-slate-300']"></span>
              <h2 class="text-sm font-semibold text-slate-900">
                {{ new Date(selected.workDate).toLocaleDateString("en-GB", { weekday: "long", day: "numeric", month: "long" }) }}
              </h2>
              <button
                class="ml-auto flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50"
                @click="openCorrectionModal(selected)"
              >
                <FilePenLine class="h-3.5 w-3.5" />
                Request Correction
              </button>
            </div>

            <!-- Info + map side by side when GPS available, else info only -->
            <div :class="selected.checkInLatitude !== null || selected.checkOutLatitude !== null ? 'grid lg:grid-cols-[220px_1fr]' : ''">
              <!-- Info -->
              <div class="space-y-3 p-4 text-sm">
                <div class="flex items-center justify-between">
                  <span class="text-slate-500">Status</span>
                  <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', statusColors[selected.status]?.replace(/hover:\S+/g, '') || 'bg-slate-100 text-slate-500']">
                    {{ statusLabels[selected.status] || selected.status }}
                  </span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-500">Check In</span>
                  <span class="font-medium text-slate-900">{{ formatTime(selected.checkInAt) }}</span>
                </div>
                <div v-if="selected.checkInAt" class="flex items-center justify-between">
                  <span class="text-slate-500">GPS In</span>
                  <div class="flex items-center gap-1 text-xs text-slate-400">
                    <component :is="selected.checkInWithinRadius === false ? MapPinOff : MapPin" class="h-3.5 w-3.5" />
                    {{ selected.checkInWithinRadius === null ? '—' : selected.checkInWithinRadius ? 'In office' : 'Outside' }}
                  </div>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-500">Check Out</span>
                  <span class="font-medium text-slate-900">{{ formatTime(selected.checkOutAt) }}</span>
                </div>
                <div v-if="selected.checkOutAt" class="flex items-center justify-between">
                  <span class="text-slate-500">GPS Out</span>
                  <div class="flex items-center gap-1 text-xs text-slate-400">
                    <component :is="selected.checkOutWithinRadius === false ? MapPinOff : MapPin" class="h-3.5 w-3.5" />
                    {{ selected.checkOutWithinRadius === null ? '—' : selected.checkOutWithinRadius ? 'In office' : 'Outside' }}
                  </div>
                </div>
                <div v-if="selected.notes" class="border-t border-slate-100 pt-2">
                  <p class="mb-1 text-xs font-medium text-slate-500">Notes</p>
                  <p class="text-slate-700">{{ selected.notes }}</p>
                </div>
              </div>

              <!-- Map -->
              <div v-if="selected.checkInLatitude !== null || selected.checkOutLatitude !== null" class="border-t border-slate-100 lg:border-l lg:border-t-0">
                <!-- Tabs -->
                <div class="flex border-b border-slate-100">
                  <button
                    v-if="selected.checkInLatitude !== null"
                    :class="['flex-1 py-2 text-xs font-semibold transition-colors', mapTab === 'checkin' ? 'border-b-2 border-slate-900 text-slate-900' : 'text-slate-400 hover:text-slate-600']"
                    @click="mapTab = 'checkin'"
                  >
                    Check-in
                  </button>
                  <button
                    v-if="selected.checkOutLatitude !== null"
                    :class="['flex-1 py-2 text-xs font-semibold transition-colors', mapTab === 'checkout' ? 'border-b-2 border-slate-900 text-slate-900' : 'text-slate-400 hover:text-slate-600']"
                    @click="mapTab = 'checkout'"
                  >
                    Check-out
                  </button>
                </div>

                <div v-if="mapTab === 'checkin' && selected.checkInLatitude !== null">
                  <iframe
                    :src="osmEmbedUrl(selected.checkInLatitude, selected.checkInLongitude!)"
                    class="h-56 w-full border-0"
                    loading="lazy"
                    referrerpolicy="no-referrer"
                  />
                  <div class="flex items-center justify-between px-3 py-1.5">
                    <span class="text-xs text-slate-400">{{ selected.checkInLatitude.toFixed(6) }}, {{ selected.checkInLongitude!.toFixed(6) }}</span>
                    <a :href="osmOpenUrl(selected.checkInLatitude, selected.checkInLongitude!)" target="_blank" rel="noopener noreferrer" class="text-xs text-blue-600 hover:underline">Open in OSM ↗</a>
                  </div>
                </div>

                <div v-if="mapTab === 'checkout' && selected.checkOutLatitude !== null">
                  <iframe
                    :src="osmEmbedUrl(selected.checkOutLatitude, selected.checkOutLongitude!)"
                    class="h-56 w-full border-0"
                    loading="lazy"
                    referrerpolicy="no-referrer"
                  />
                  <div class="flex items-center justify-between px-3 py-1.5">
                    <span class="text-xs text-slate-400">{{ selected.checkOutLatitude.toFixed(6) }}, {{ selected.checkOutLongitude!.toFixed(6) }}</span>
                    <a :href="osmOpenUrl(selected.checkOutLatitude, selected.checkOutLongitude!)" target="_blank" rel="noopener noreferrer" class="text-xs text-blue-600 hover:underline">Open in OSM ↗</a>
                  </div>
                </div>
              </div>
            </div>
          </article>
        </div>

        <!-- Right sidebar: summary -->
        <div class="space-y-4">
          <!-- Hint when nothing selected -->
          <article v-if="!selected" class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="px-4 py-8 text-center text-sm text-slate-400">
              Click a day on the calendar to see details.
            </div>
          </article>

          <!-- Monthly summary -->
          <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-2.5">
              <h2 class="text-sm font-semibold text-slate-900">Month Summary</h2>
            </div>
            <div class="divide-y divide-slate-100">
              <div v-for="(dot, key) in statusDot" :key="key" class="flex items-center justify-between px-4 py-2">
                <div class="flex items-center gap-2">
                  <span :class="['h-2 w-2 rounded-full', dot]"></span>
                  <span class="text-sm text-slate-600">{{ statusLabels[key] }}</span>
                </div>
                <span class="text-sm font-semibold text-slate-900">
                  {{ Object.values(logMap).filter(l => l.status === key).length }}
                </span>
              </div>
            </div>
          </article>
        </div>
      </div>
    </div>

    <!-- Correction Request Modal -->
    <Teleport to="body">
      <div
        v-if="correctionModal.open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        @click.self="correctionModal.open = false"
      >
        <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white shadow-xl">
          <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
            <FilePenLine class="h-4 w-4 text-slate-500" />
            <h2 class="text-sm font-semibold text-slate-900">Request Attendance Correction</h2>
          </div>
          <div class="space-y-4 px-5 py-5">
            <p class="text-xs text-slate-500">
              Enter the correct times for
              <span class="font-semibold text-slate-700">
                {{ correctionModal.log ? new Date(correctionModal.log.workDate).toLocaleDateString("en-GB", { weekday: "long", day: "numeric", month: "long" }) : "" }}
              </span>.
              Leave blank if no change is needed for that field.
            </p>

            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1.5">
                <label class="text-xs font-medium text-slate-600">Corrected Check-in</label>
                <input
                  v-model="correctionModal.checkInAt"
                  type="datetime-local"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                />
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-medium text-slate-600">Corrected Check-out</label>
                <input
                  v-model="correctionModal.checkOutAt"
                  type="datetime-local"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                />
              </div>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-medium text-slate-600">Reason <span class="text-slate-400">(min 10 chars)</span></label>
              <textarea
                v-model="correctionModal.reason"
                rows="3"
                placeholder="Explain why this correction is needed…"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>

            <div class="flex gap-2 pt-1">
              <button
                class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                @click="correctionModal.open = false"
              >Cancel</button>
              <button
                :disabled="correctionModal.submitting"
                class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:opacity-50"
                @click="submitCorrection"
              >
                {{ correctionModal.submitting ? "Submitting…" : "Submit Request" }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>
