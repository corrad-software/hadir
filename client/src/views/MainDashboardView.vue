<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { Clock, LogIn, LogOut, CheckCircle2, MapPin, MapPinOff, Navigation, UserCheck, AlertTriangle, MapPinOff as MapPinOffIcon } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { useToast } from "@/composables/useToast";
import { useAuthStore } from "@/stores/auth";
import { getTodayAttendance, checkIn, checkOut, getAttendanceTodayStats } from "@/api/cms";
import type { AttendanceLog } from "@/types";

const toast = useToast();
const auth = useAuthStore();

const todayLog = ref<AttendanceLog | null>(null);
const loadingAttendance = ref(false);
const acting = ref(false);

const attendanceStats = ref<{ checkedIn: number; late: number; outsideRadius: number } | null>(null);
const isAdminOrHr = computed(() => ["admin", "hr_admin"].includes((auth.user?.role ?? "").toLowerCase()));

const locationModal = ref<{
  open: boolean;
  action: "checkin" | "checkout" | null;
  latitude: number | null;
  longitude: number | null;
  acquiring: boolean;
}>({ open: false, action: null, latitude: null, longitude: null, acquiring: false });

const checkedIn = computed(() => !!todayLog.value?.checkInAt);
const checkedOut = computed(() => !!todayLog.value?.checkOutAt);

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

function formatTime(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" });
}

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

async function openLocationModal(action: "checkin" | "checkout") {
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
}

function cancelLocationModal() {
  locationModal.value = { open: false, action: null, latitude: null, longitude: null, acquiring: false };
}

async function confirmLocation() {
  const { action, latitude, longitude } = locationModal.value;
  if (!action || latitude === null || longitude === null) return;
  cancelLocationModal();
  acting.value = true;
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
  } catch (e) {
    toast.error(action === "checkin" ? "Check-in failed" : "Check-out failed", e instanceof Error ? e.message : "");
  } finally {
    acting.value = false;
  }
}

async function loadAttendance() {
  loadingAttendance.value = true;
  try {
    const res = await getTodayAttendance();
    todayLog.value = res.data;
  } catch {
    todayLog.value = null;
  } finally {
    loadingAttendance.value = false;
  }
}

async function loadStats() {
  if (!isAdminOrHr.value) return;
  try {
    const res = await getAttendanceTodayStats();
    attendanceStats.value = res.data;
  } catch {
    attendanceStats.value = null;
  }
}

const greeting = computed(() => {
  const h = new Date().getHours();
  if (h < 12) return "Good morning";
  if (h < 17) return "Good afternoon";
  return "Good evening";
});

const todayLabel = new Date().toLocaleDateString("en-GB", { weekday: "long", day: "numeric", month: "long", year: "numeric" });

onMounted(() => { loadAttendance(); loadStats(); });
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-5">

      <!-- Hero + Check-In row -->
      <div class="grid gap-4 lg:grid-cols-2">

        <!-- Greeting -->
        <div class="rounded-2xl border border-slate-200 bg-gradient-to-r from-slate-50 to-white p-6 shadow-sm flex flex-col justify-center">
          <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ todayLabel }}</p>
          <h1 class="mt-2 text-lg font-semibold text-slate-900">{{ greeting }}, {{ auth.user?.name?.split(" ")[0] ?? "there" }}</h1>
          <p class="mt-1 text-sm text-slate-500">Here's a quick look at your day.</p>
        </div>

        <!-- Quick Check-In Card -->
      <article class="overflow-hidden rounded-xl border border-indigo-900 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 shadow-lg">
        <div class="flex items-center gap-2 border-b border-white/10 px-5 py-3">
          <Clock class="h-4 w-4 text-indigo-300" />
          <h2 class="text-sm font-semibold text-white">Today's Attendance</h2>
          <router-link to="/admin/attendance/checkin" class="ml-auto text-xs text-indigo-300 hover:text-white transition-colors">
            View full history →
          </router-link>
        </div>

        <div class="flex flex-wrap items-center gap-8 px-5 py-5">
          <!-- Check-in time -->
          <div class="space-y-1 min-w-[80px]">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-300">Check In</p>
            <p class="text-2xl font-bold text-white">{{ formatTime(todayLog?.checkInAt ?? null) }}</p>
            <div v-if="todayLog?.checkInAt" class="flex items-center gap-1 text-xs text-indigo-300">
              <component :is="todayLog.checkInWithinRadius === false ? MapPinOff : MapPin" class="h-3 w-3" />
              {{ todayLog.checkInWithinRadius === null ? 'No GPS' : todayLog.checkInWithinRadius ? 'In office' : 'Outside' }}
            </div>
          </div>

          <!-- Divider -->
          <div class="h-10 w-px bg-white/10" />

          <!-- Check-out time -->
          <div class="space-y-1 min-w-[80px]">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-300">Check Out</p>
            <p class="text-2xl font-bold text-white">{{ formatTime(todayLog?.checkOutAt ?? null) }}</p>
            <div v-if="todayLog?.checkOutAt" class="flex items-center gap-1 text-xs text-indigo-300">
              <component :is="todayLog.checkOutWithinRadius === false ? MapPinOff : MapPin" class="h-3 w-3" />
              {{ todayLog.checkOutWithinRadius === null ? 'No GPS' : todayLog.checkOutWithinRadius ? 'In office' : 'Outside' }}
            </div>
          </div>

          <!-- Divider -->
          <div class="h-10 w-px bg-white/10" />

          <!-- Status -->
          <div class="space-y-1">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-300">Status</p>
            <span
              v-if="todayLog"
              :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', statusColors[todayLog.status] ?? 'bg-slate-100 text-slate-500']"
            >
              {{ statusLabels[todayLog.status] ?? todayLog.status }}
            </span>
            <span v-else class="text-sm text-indigo-400">—</span>
          </div>

          <!-- Action buttons -->
          <div class="ml-auto flex items-center gap-2">
            <div v-if="loadingAttendance" class="h-5 w-5 animate-spin rounded-full border-2 border-white/20 border-t-white" />

            <template v-else-if="checkedIn && checkedOut">
              <div class="flex items-center gap-2 text-sm text-emerald-400">
                <CheckCircle2 class="h-4 w-4" />
                Done for today
              </div>
            </template>

            <template v-else>
              <button
                v-if="!checkedIn"
                :disabled="acting"
                class="flex items-center gap-2 rounded-lg bg-white px-5 py-2 text-sm font-semibold text-slate-900 shadow transition-colors hover:bg-indigo-100 disabled:opacity-50"
                @click="openLocationModal('checkin')"
              >
                <LogIn class="h-4 w-4" />
                {{ acting ? "Please wait…" : "Check In" }}
              </button>
              <button
                v-if="checkedIn && !checkedOut"
                :disabled="acting"
                class="flex items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-5 py-2 text-sm font-semibold text-white backdrop-blur-sm transition-colors hover:bg-white/20 disabled:opacity-50"
                @click="openLocationModal('checkout')"
              >
                <LogOut class="h-4 w-4" />
                {{ acting ? "Please wait…" : "Check Out" }}
              </button>
            </template>
          </div>
        </div>
      </article>

      </div><!-- end hero row -->

      <!-- Admin Attendance Stats -->
      <div v-if="isAdminOrHr && attendanceStats" class="grid grid-cols-3 gap-3">
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Checked In Today</span>
            <UserCheck class="h-4 w-4 text-emerald-500" />
          </div>
          <p class="mt-2 text-2xl font-bold text-slate-900">{{ attendanceStats.checkedIn }}</p>
          <router-link to="/admin/attendance/team-map" class="mt-0.5 text-xs text-violet-600 hover:underline">View map →</router-link>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Late Today</span>
            <AlertTriangle class="h-4 w-4 text-amber-500" />
          </div>
          <p class="mt-2 text-2xl font-bold text-amber-600">{{ attendanceStats.late }}</p>
          <router-link to="/admin/attendance/records" class="mt-0.5 text-xs text-violet-600 hover:underline">View records →</router-link>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Outside Radius</span>
            <MapPinOffIcon class="h-4 w-4 text-rose-500" />
          </div>
          <p class="mt-2 text-2xl font-bold text-rose-600">{{ attendanceStats.outsideRadius }}</p>
          <p class="mt-0.5 text-xs text-slate-400">Check-in or out</p>
        </div>
      </div>

    </div>

    <!-- Location Modal -->
    <Teleport to="body">
      <div
        v-if="locationModal.open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        @click.self="cancelLocationModal"
      >
        <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white shadow-xl">
          <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
            <Navigation class="h-4 w-4 text-blue-600" />
            <h2 class="text-sm font-semibold text-slate-900">
              Confirm Location — {{ locationModal.action === "checkin" ? "Check In" : "Check Out" }}
            </h2>
          </div>

          <div v-if="locationModal.acquiring" class="flex flex-col items-center gap-3 px-5 py-8 text-center">
            <div class="h-8 w-8 animate-spin rounded-full border-2 border-slate-200 border-t-blue-600" />
            <p class="text-sm text-slate-500">Acquiring your location…</p>
          </div>

          <div v-else class="space-y-4 px-5 py-5">
            <p class="text-sm text-slate-600">Your current GPS coordinates will be recorded. Confirm to proceed.</p>
            <div class="space-y-1 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
              <div class="flex items-center justify-between text-xs">
                <span class="font-medium text-slate-500">Latitude</span>
                <span class="font-mono text-slate-800">{{ locationModal.latitude?.toFixed(6) }}</span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="font-medium text-slate-500">Longitude</span>
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
                {{ locationModal.action === "checkin" ? "Check In" : "Check Out" }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>
