<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from "vue";
import { Clock, MapPin, MapPinOff, LogIn, LogOut, CheckCircle2, Navigation, Loader2 } from "lucide-vue-next";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { useToast } from "@/composables/useToast";
import { getTodayAttendance, checkIn, checkOut, listMyAttendance } from "@/api/cms";
import type { AttendanceLog } from "@/types";

const toast = useToast();
const todayLog = ref<AttendanceLog | null>(null);
const recentLogs = ref<AttendanceLog[]>([]);
const loading = ref(false);
const recentPage = ref(1);
const recentLimit = 10;
const recentTotal = ref(0);
const recentTotalPages = ref(0);

// Live clock
const liveClock = ref(new Date());
let clockTimer: number | null = null;

// Pre-fetched GPS position (acquired silently on mount)
const prefetchedPosition = ref<{ latitude: number; longitude: number } | null>(null);
const prefetchingGps = ref(false);

async function prefetchGps() {
  prefetchingGps.value = true;
  const pos = await getPosition();
  prefetchedPosition.value = pos;
  prefetchingGps.value = false;
}

// Location confirmation modal state
const locationModal = ref<{
  open: boolean;
  action: "checkin" | "checkout" | null;
  latitude: number | null;
  longitude: number | null;
  acquiring: boolean;
}>({ open: false, action: null, latitude: null, longitude: null, acquiring: false });

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

const checkedIn = computed(() => !!todayLog.value?.checkInAt);
const checkedOut = computed(() => !!todayLog.value?.checkOutAt);

async function getPosition(): Promise<{ latitude: number; longitude: number } | null> {
  return new Promise((resolve) => {
    if (!navigator.geolocation) { resolve(null); return; }
    navigator.geolocation.getCurrentPosition(
      (pos) => resolve({ latitude: pos.coords.latitude, longitude: pos.coords.longitude }),
      () => resolve(null),
      { timeout: 8000 }
    );
  });
}

function formatTime(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" });
}

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short" });
}

async function loadRecent() {
  const params = `?page=${recentPage.value}&limit=${recentLimit}&sort_by=work_date&sort_dir=desc`;
  const res = await listMyAttendance(params);
  recentLogs.value = res.data;
  recentTotal.value = Number(res.meta?.total ?? 0);
  recentTotalPages.value = Number(res.meta?.totalPages ?? 0);
}

async function loadData() {
  const [todayRes] = await Promise.allSettled([getTodayAttendance()]);
  todayLog.value = todayRes.status === "fulfilled" ? todayRes.value.data : null;
  await loadRecent();
}

async function goToPage(p: number) {
  recentPage.value = p;
  await loadRecent();
}

async function openLocationModal(action: "checkin" | "checkout") {
  if (prefetchedPosition.value) {
    locationModal.value = {
      open: true, action,
      latitude: prefetchedPosition.value.latitude,
      longitude: prefetchedPosition.value.longitude,
      acquiring: false,
    };
    return;
  }

  locationModal.value = { open: true, action, latitude: null, longitude: null, acquiring: true };

  const pos = await getPosition();

  if (!pos) {
    locationModal.value.open = false;
    toast.error("GPS required", "Allow location access in your browser and try again.");
    return;
  }

  locationModal.value.latitude = pos.latitude;
  locationModal.value.longitude = pos.longitude;
  locationModal.value.acquiring = false;
  prefetchedPosition.value = pos;
}

function cancelLocationModal() {
  locationModal.value = { open: false, action: null, latitude: null, longitude: null, acquiring: false };
}

async function confirmLocation() {
  const { action, latitude, longitude } = locationModal.value;
  if (!action || latitude === null || longitude === null) return;

  cancelLocationModal();
  loading.value = true;

  try {
    if (action === "checkin") {
      const res = await checkIn({ latitude, longitude });
      todayLog.value = res.data;
      toast.success("Checked in successfully!");
    } else {
      const res = await checkOut({ latitude, longitude });
      todayLog.value = res.data;
      toast.success("Checked out successfully!");
    }
    await loadData();
  } catch (e) {
    toast.error(action === "checkin" ? "Check-in failed" : "Check-out failed", e instanceof Error ? e.message : "");
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadData();
  prefetchGps();
  clockTimer = window.setInterval(() => { liveClock.value = new Date(); }, 1000);
});

onBeforeUnmount(() => {
  if (clockTimer) window.clearInterval(clockTimer);
});
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <AppBreadcrumb :items="[{ label: 'Attendance' }, { label: 'Check In / Out' }]" />

      <!-- Mobile Hero Card -->
      <article class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <!-- Live clock banner -->
        <div class="flex flex-col items-center justify-center bg-slate-900 py-5 px-4 text-center">
          <p class="text-4xl font-bold tabular-nums tracking-tight text-white">
            {{ liveClock.toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit", second: "2-digit" }) }}
          </p>
          <p class="mt-1 text-xs text-slate-400">
            {{ liveClock.toLocaleDateString("en-GB", { weekday: "long", day: "numeric", month: "long", year: "numeric" }) }}
          </p>
          <div v-if="prefetchingGps" class="mt-2 flex items-center gap-1.5 text-[11px] text-slate-500">
            <Loader2 class="h-3 w-3 animate-spin" />
            Acquiring GPS…
          </div>
          <div v-else-if="prefetchedPosition" class="mt-2 flex items-center gap-1.5 text-[11px] text-emerald-400">
            <MapPin class="h-3 w-3" />
            Location ready
          </div>
          <div v-else class="mt-2 flex items-center gap-1.5 text-[11px] text-amber-400">
            <MapPinOff class="h-3 w-3" />
            No GPS signal
          </div>
        </div>

        <div class="p-4">
          <!-- Today's attendance info -->
          <div v-if="todayLog" class="mb-4 grid grid-cols-3 gap-3">
            <div class="rounded-lg bg-slate-50 p-2.5 text-center">
              <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Check In</p>
              <p class="mt-1 text-base font-bold text-slate-900">{{ formatTime(todayLog.checkInAt) }}</p>
              <div class="mt-0.5 flex items-center justify-center gap-0.5 text-[10px] text-slate-400">
                <component :is="todayLog.checkInWithinRadius === false ? MapPinOff : MapPin" class="h-2.5 w-2.5" />
                {{ todayLog.checkInWithinRadius === null ? 'No GPS' : todayLog.checkInWithinRadius ? 'In office' : 'Outside' }}
              </div>
            </div>
            <div class="rounded-lg bg-slate-50 p-2.5 text-center">
              <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Check Out</p>
              <p class="mt-1 text-base font-bold text-slate-900">{{ formatTime(todayLog.checkOutAt) }}</p>
              <div v-if="todayLog.checkOutAt" class="mt-0.5 flex items-center justify-center gap-0.5 text-[10px] text-slate-400">
                <component :is="todayLog.checkOutWithinRadius === false ? MapPinOff : MapPin" class="h-2.5 w-2.5" />
                {{ todayLog.checkOutWithinRadius === null ? 'No GPS' : todayLog.checkOutWithinRadius ? 'In office' : 'Outside' }}
              </div>
            </div>
            <div class="rounded-lg bg-slate-50 p-2.5 text-center">
              <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Status</p>
              <span :class="['mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium', statusColors[todayLog.status] || 'bg-slate-100 text-slate-500']">
                {{ statusLabels[todayLog.status] || todayLog.status }}
              </span>
            </div>
          </div>

          <div v-if="!todayLog" class="mb-4 text-sm text-slate-400">No attendance record yet today.</div>

          <!-- Action buttons — full width on mobile -->
          <div v-if="checkedIn && checkedOut" class="flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 py-4 text-sm font-medium text-emerald-700">
            <CheckCircle2 class="h-5 w-5" />
            All done for today!
          </div>
          <button
            v-else-if="!checkedIn"
            :disabled="loading"
            class="flex w-full items-center justify-center gap-3 rounded-xl bg-slate-900 py-4 text-base font-semibold text-white shadow transition-colors hover:bg-slate-800 active:scale-95 disabled:opacity-50"
            @click="openLocationModal('checkin')"
          >
            <LogIn class="h-5 w-5" />
            {{ loading ? 'Please wait…' : 'Check In' }}
          </button>
          <button
            v-else-if="checkedIn && !checkedOut"
            :disabled="loading"
            class="flex w-full items-center justify-center gap-3 rounded-xl border-2 border-slate-300 bg-white py-4 text-base font-semibold text-slate-700 shadow transition-colors hover:bg-slate-50 active:scale-95 disabled:opacity-50"
            @click="openLocationModal('checkout')"
          >
            <LogOut class="h-5 w-5" />
            {{ loading ? 'Please wait…' : 'Check Out' }}
          </button>
        </div>
      </article>

      <!-- Recent History -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
          <Clock class="h-4 w-4 text-slate-400" />
          <h2 class="text-sm font-semibold text-slate-900">Recent Attendance</h2>
          <span v-if="recentTotal > 0" class="ml-auto text-xs text-slate-400">{{ recentTotal }} records</span>
        </div>
        <div v-if="recentLogs.length === 0" class="px-4 py-8 text-center text-sm text-slate-400">No records yet.</div>
        <table v-else class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-100 text-left">
              <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Check In</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Check Out</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="log in recentLogs" :key="log.id" class="transition-colors hover:bg-slate-50">
              <td class="px-4 py-2 font-medium text-slate-900">{{ formatDate(log.workDate) }}</td>
              <td class="px-4 py-2 text-slate-500">{{ formatTime(log.checkInAt) }}</td>
              <td class="px-4 py-2 text-slate-500">{{ formatTime(log.checkOutAt) }}</td>
              <td class="px-4 py-2">
                <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', statusColors[log.status] || 'bg-slate-100 text-slate-500']">
                  {{ statusLabels[log.status] || log.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="recentTotalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-2.5">
          <span class="text-xs text-slate-400">Page {{ recentPage }} of {{ recentTotalPages }}</span>
          <div class="flex items-center gap-1">
            <button
              :disabled="recentPage <= 1"
              class="rounded border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40"
              @click="goToPage(recentPage - 1)"
            >Prev</button>
            <button
              :disabled="recentPage >= recentTotalPages"
              class="rounded border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40"
              @click="goToPage(recentPage + 1)"
            >Next</button>
          </div>
        </div>
      </article>
    </div>

    <!-- Location Confirmation Modal -->
    <Teleport to="body">
      <div
        v-if="locationModal.open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        @click.self="cancelLocationModal"
      >
        <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white shadow-xl">
          <!-- Modal header -->
          <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
            <Navigation class="h-4 w-4 text-blue-600" />
            <h2 class="text-sm font-semibold text-slate-900">
              Confirm Location — {{ locationModal.action === 'checkin' ? 'Check In' : 'Check Out' }}
            </h2>
          </div>

          <!-- Acquiring state -->
          <div v-if="locationModal.acquiring" class="flex flex-col items-center gap-3 px-5 py-8 text-center">
            <div class="h-8 w-8 animate-spin rounded-full border-2 border-slate-200 border-t-blue-600" />
            <p class="text-sm text-slate-500">Acquiring your location…</p>
          </div>

          <!-- Location acquired -->
          <div v-else class="px-5 py-5 space-y-4">
            <p class="text-sm text-slate-600">Your current GPS coordinates will be recorded. Confirm to proceed.</p>
            <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 space-y-1">
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500 font-medium">Latitude</span>
                <span class="font-mono text-slate-800">{{ locationModal.latitude?.toFixed(6) }}</span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500 font-medium">Longitude</span>
                <span class="font-mono text-slate-800">{{ locationModal.longitude?.toFixed(6) }}</span>
              </div>
            </div>
            <div class="flex gap-2 pt-1">
              <button
                class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                @click="cancelLocationModal"
              >
                Cancel
              </button>
              <button
                class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-800"
                @click="confirmLocation"
              >
                {{ locationModal.action === 'checkin' ? 'Check In' : 'Check Out' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>
