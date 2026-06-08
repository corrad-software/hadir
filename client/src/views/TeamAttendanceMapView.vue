<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from "vue";
import { Users, RefreshCw, CheckCircle2, XCircle, Clock, ChevronLeft, ChevronRight } from "lucide-vue-next";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import AdminLayout from "@/layouts/AdminLayout.vue";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AppTooltip from "@/components/AppTooltip.vue";
import AppSelect from "@/components/AppSelect.vue";
import { useToast } from "@/composables/useToast";
import { getTeamToday } from "@/api/cms";
import { API_BASE_URL } from "@/env";
import type { TeamMemberAttendance } from "@/types";

const toast = useToast();
const members = ref<TeamMemberAttendance[]>([]);
const loading = ref(false);
const mapEl = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
const markers: L.Marker[] = [];

const statusColors: Record<string, string> = {
  on_time:     "bg-emerald-100 text-emerald-700",
  late:        "bg-amber-100 text-amber-700",
  early_leave: "bg-orange-100 text-orange-700",
  absent:      "bg-rose-100 text-rose-700",
  pending:     "bg-slate-100 text-slate-500",
};

const statusLabels: Record<string, string> = {
  on_time:     "On Time",
  late:        "Late",
  early_leave: "Early Leave",
  absent:      "Absent",
  pending:     "Pending",
};

const present = computed(() => members.value.filter((m) => m.checkedIn));
const absent  = computed(() => members.value.filter((m) => !m.checkedIn));

const filterStatus = ref<string | null>(null);
const filterLocation = ref<string | null>(null); // "in" | "out" | "no_gps"

const filteredMembers = computed(() => {
  let list = members.value;

  if (filterStatus.value) {
    if (filterStatus.value === "absent") {
      list = list.filter((m) => !m.checkedIn);
    } else {
      list = list.filter((m) => m.status === filterStatus.value);
    }
  }

  if (filterLocation.value === "in")     list = list.filter((m) => m.withinRadius === true);
  if (filterLocation.value === "out")    list = list.filter((m) => m.withinRadius === false);
  if (filterLocation.value === "no_gps") list = list.filter((m) => m.checkedIn && m.latitude === null);

  return list;
});

const PAGE_SIZE = 10;
const page = ref(1);
const totalPages = computed(() => Math.max(1, Math.ceil(filteredMembers.value.length / PAGE_SIZE)));
const pagedMembers = computed(() => {
  const start = (page.value - 1) * PAGE_SIZE;
  return filteredMembers.value.slice(start, start + PAGE_SIZE);
});

function formatTime(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" });
}

function initials(name: string) {
  return name.split(" ").map((n) => n[0]).join("").toUpperCase().slice(0, 2);
}

function resolveUrl(url: string | null): string {
  if (!url) return "";
  if (url.startsWith("http")) return url;
  return `${API_BASE_URL}${url}`;
}

function markerColor(m: TeamMemberAttendance): string {
  if (!m.checkedIn) return "#f43f5e";      // rose — absent
  if (m.status === "late") return "#f59e0b"; // amber — late
  return "#10b981";                          // emerald — on time
}

function makeIcon(m: TeamMemberAttendance): L.DivIcon {
  const color = markerColor(m);
  const avatar = m.photoUrl
    ? `<img src="${resolveUrl(m.photoUrl)}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid ${color}" />`
    : `<div style="width:32px;height:32px;border-radius:50%;background:${color};display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:700;border:2px solid ${color}">${initials(m.name)}</div>`;

  return L.divIcon({
    className: "",
    html: `<div style="position:relative">
      ${avatar}
      <div style="position:absolute;bottom:-2px;right:-2px;width:10px;height:10px;border-radius:50%;background:${color};border:2px solid #fff"></div>
    </div>`,
    iconSize: [36, 36],
    iconAnchor: [18, 18],
    popupAnchor: [0, -20],
  });
}

function buildPopup(m: TeamMemberAttendance): string {
  const status = statusLabels[m.status] ?? m.status;
  return `
    <div style="min-width:160px;font-family:inherit">
      <p style="font-weight:600;font-size:13px;margin:0 0 4px">${m.name}</p>
      <p style="font-size:11px;color:#64748b;margin:0 0 2px">Check In: <strong>${formatTime(m.checkInAt)}</strong></p>
      <p style="font-size:11px;color:#64748b;margin:0 0 4px">Check Out: <strong>${formatTime(m.checkOutAt)}</strong></p>
      <span style="font-size:10px;padding:2px 8px;border-radius:999px;background:${markerColor(m)}22;color:${markerColor(m)};font-weight:600">${status}</span>
    </div>
  `;
}

function clearMarkers() {
  markers.forEach((m) => m.remove());
  markers.length = 0;
}

function plotMarkers() {
  if (!map) return;
  clearMarkers();

  const withCoords = members.value.filter((m) => m.latitude !== null && m.longitude !== null);

  withCoords.forEach((m) => {
    const marker = L.marker([m.latitude!, m.longitude!], { icon: makeIcon(m) })
      .bindPopup(buildPopup(m), { maxWidth: 220 });
    marker.addTo(map!);
    markers.push(marker);
  });

  if (withCoords.length > 0) {
    const bounds = L.latLngBounds(withCoords.map((m) => [m.latitude!, m.longitude!] as [number, number]));
    map.fitBounds(bounds, { padding: [60, 60], maxZoom: 15 });
  }
}

async function load() {
  loading.value = true;
  try {
    const res = await getTeamToday();
    members.value = res.data;
    page.value = 1;
    filterStatus.value = null;
    filterLocation.value = null;
    await nextTick();
    plotMarkers();
  } catch (e) {
    toast.error("Failed to load team attendance", e instanceof Error ? e.message : "");
  } finally {
    loading.value = false;
  }
}

function focusMember(m: TeamMemberAttendance) {
  if (!map || m.latitude === null || m.longitude === null) return;
  map.setView([m.latitude, m.longitude], 16);
  const marker = markers.find((mk) => {
    const ll = mk.getLatLng();
    return ll.lat === m.latitude && ll.lng === m.longitude;
  });
  marker?.openPopup();
}

onMounted(async () => {
  await load();
  await nextTick();
  if (mapEl.value) {
    map = L.map(mapEl.value, { zoomControl: true }).setView([3.139, 101.687], 12);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
      maxZoom: 19,
    }).addTo(map);
    plotMarkers();
  }
});

onUnmounted(() => {
  map?.remove();
  map = null;
});

const todayLabel = new Date().toLocaleDateString("en-GB", { weekday: "long", day: "numeric", month: "long" });
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <AppBreadcrumb :items="[{ label: 'Attendance' }, { label: 'Team Map' }]" />

      <!-- Header -->
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="page-title">Team Attendance Map</h1>
          <p class="mt-0.5 text-sm text-slate-500">{{ todayLabel }} — see who's in and where they checked in from.</p>
        </div>
        <button
          class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 disabled:opacity-50"
          :disabled="loading"
          @click="load"
        >
          <RefreshCw class="h-3.5 w-3.5" :class="loading ? 'animate-spin' : ''" />
          Refresh
        </button>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-3">
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total</span>
            <Users class="h-4 w-4 text-slate-400" />
          </div>
          <p class="mt-1 text-2xl font-bold text-slate-900">{{ members.length }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Present</span>
            <CheckCircle2 class="h-4 w-4 text-emerald-500" />
          </div>
          <p class="mt-1 text-2xl font-bold text-emerald-600">{{ present.length }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Absent</span>
            <XCircle class="h-4 w-4 text-rose-500" />
          </div>
          <p class="mt-1 text-2xl font-bold text-rose-600">{{ absent.length }}</p>
        </div>
      </div>

      <!-- Map -->
      <div class="overflow-hidden rounded-lg border border-slate-200 shadow-sm" style="height: 520px">
        <div ref="mapEl" class="h-full w-full" />
      </div>

      <!-- Member list (below map) -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-4 py-2.5">
          <div class="flex items-center gap-2">
            <Users class="h-4 w-4 text-violet-600" />
            <h2 class="text-sm font-semibold text-slate-900">Members</h2>
            <span class="text-xs text-slate-400">({{ filteredMembers.length }})</span>
          </div>
          <div class="ml-auto flex items-center gap-2">
            <AppSelect
              v-model="filterStatus"
              :options="[
                { value: 'on_time',     label: 'On Time' },
                { value: 'late',        label: 'Late' },
                { value: 'early_leave', label: 'Early Leave' },
                { value: 'absent',      label: 'Absent' },
              ]"
              placeholder="All Statuses"
              class="w-36"
              @update:model-value="page = 1"
            />
            <AppSelect
              v-model="filterLocation"
              :options="[
                { value: 'in',     label: 'In Office' },
                { value: 'out',    label: 'Outside' },
                { value: 'no_gps', label: 'No GPS' },
              ]"
              placeholder="All Locations"
              class="w-36"
              @update:model-value="page = 1"
            />
          </div>
        </div>

        <div v-if="loading" class="px-4 py-8 text-center text-sm text-slate-400">Loading…</div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Check In</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Check Out</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Location</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="m in pagedMembers"
                :key="m.id"
                class="transition-colors"
                :class="m.latitude !== null ? 'cursor-pointer hover:bg-slate-50' : ''"
                @click="focusMember(m)"
              >
                <td class="px-4 py-2.5">
                  <div class="flex items-center gap-2.5">
                    <div class="relative shrink-0">
                      <img
                        v-if="m.photoUrl"
                        :src="resolveUrl(m.photoUrl)"
                        :alt="m.name"
                        class="h-7 w-7 rounded-full object-cover"
                        :class="!m.checkedIn ? 'opacity-50' : ''"
                      />
                      <div
                        v-else
                        class="flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-semibold text-white"
                        :class="m.checkedIn ? 'bg-gradient-to-br from-emerald-400 to-teal-500' : 'bg-slate-300'"
                      >
                        {{ initials(m.name) }}
                      </div>
                      <div
                        class="absolute -bottom-0.5 -right-0.5 h-2 w-2 rounded-full border border-white"
                        :class="m.checkedIn ? 'bg-emerald-500' : 'bg-rose-400'"
                      />
                    </div>
                    <span class="font-medium text-slate-900">{{ m.name }}</span>
                  </div>
                </td>
                <td class="px-4 py-2.5">
                  <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', statusColors[m.status] ?? 'bg-slate-100 text-slate-500']">
                    {{ statusLabels[m.status] ?? m.status }}
                  </span>
                </td>
                <td class="px-4 py-2.5 text-xs text-slate-600">{{ formatTime(m.checkInAt) }}</td>
                <td class="px-4 py-2.5 text-xs text-slate-600">{{ formatTime(m.checkOutAt) }}</td>
                <td class="px-4 py-2.5 text-xs text-slate-400">
                  <span v-if="m.withinRadius === true" class="text-emerald-600">In office</span>
                  <span v-else-if="m.withinRadius === false" class="text-amber-600">Outside</span>
                  <span v-else>—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-2.5">
          <span class="text-xs text-slate-400">Page {{ page }} of {{ totalPages }} · {{ members.length }} members</span>
          <div class="flex items-center gap-1">
            <button
              class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 disabled:opacity-40"
              :disabled="page <= 1"
              @click="page--"
            >
              <ChevronLeft class="h-4 w-4" />
            </button>
            <button
              v-for="p in totalPages"
              :key="p"
              class="flex h-7 w-7 items-center justify-center rounded-md text-xs font-medium transition-colors"
              :class="p === page ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-100'"
              @click="page = p"
            >
              {{ p }}
            </button>
            <button
              class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 disabled:opacity-40"
              :disabled="page >= totalPages"
              @click="page++"
            >
              <ChevronRight class="h-4 w-4" />
            </button>
          </div>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
