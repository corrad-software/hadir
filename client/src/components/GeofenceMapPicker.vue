<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, watch } from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

// Fix Vite broken default marker icon path
delete (L.Icon.Default.prototype as unknown as Record<string, unknown>)._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
});

const props = defineProps<{
  latitude: number | null;
  longitude: number | null;
  radiusMeters: number;
  locationName?: string | null;
}>();

const emit = defineEmits<{
  "update:latitude": [value: number];
  "update:longitude": [value: number];
  "update:radiusMeters": [value: number];
  "update:locationName": [value: string];
}>();

const mapRef = ref<HTMLDivElement | null>(null);
const searchQuery = ref("");
const searchResults = ref<{ display_name: string; lat: string; lon: string }[]>([]);
const showDropdown = ref(false);
const searching = ref(false);

let map: L.Map | null = null;
let marker: L.Marker | null = null;
let circle: L.Circle | null = null;

const DEFAULT_LAT = 3.1390;
const DEFAULT_LNG = 101.6869;

function updateMarkerAndCircle(lat: number, lng: number) {
  if (!map) return;
  const latlng: L.LatLngExpression = [lat, lng];
  if (marker) {
    marker.setLatLng(latlng);
  }
  if (circle) {
    circle.setLatLng(latlng);
  }
}

function updateCircleRadius(radius: number) {
  if (circle) {
    circle.setRadius(radius);
  }
}

function handleMapClick(e: L.LeafletMouseEvent) {
  emit("update:latitude", e.latlng.lat);
  emit("update:longitude", e.latlng.lng);
  updateMarkerAndCircle(e.latlng.lat, e.latlng.lng);
}

onMounted(() => {
  if (!mapRef.value) return;

  const initLat = props.latitude ?? DEFAULT_LAT;
  const initLng = props.longitude ?? DEFAULT_LNG;
  const initZoom = props.latitude ? 15 : 5;

  map = L.map(mapRef.value).setView([initLat, initLng], initZoom);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(map);

  marker = L.marker([initLat, initLng], { draggable: true }).addTo(map);
  circle = L.circle([initLat, initLng], {
    radius: props.radiusMeters,
    color: "#7c3aed",
    fillColor: "#7c3aed",
    fillOpacity: 0.1,
    weight: 2,
  }).addTo(map);

  marker.on("dragend", (e) => {
    const latlng = (e.target as L.Marker).getLatLng();
    emit("update:latitude", latlng.lat);
    emit("update:longitude", latlng.lng);
    if (circle) circle.setLatLng(latlng);
  });

  map.on("click", handleMapClick);
});

onBeforeUnmount(() => {
  map?.remove();
  map = null;
});

watch(() => props.latitude, (val) => {
  if (val !== null && props.longitude !== null) {
    updateMarkerAndCircle(val, props.longitude);
  }
});

watch(() => props.longitude, (val) => {
  if (val !== null && props.latitude !== null) {
    updateMarkerAndCircle(props.latitude, val);
  }
});

watch(() => props.radiusMeters, (val) => {
  updateCircleRadius(val);
});

function onSearchBlur() {
  setTimeout(() => { showDropdown.value = false; }, 200);
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function onSearchInput() {
  if (searchTimeout) clearTimeout(searchTimeout);
  if (!searchQuery.value.trim()) {
    searchResults.value = [];
    showDropdown.value = false;
    return;
  }
  searchTimeout = setTimeout(doSearch, 400);
}

async function doSearch() {
  searching.value = true;
  try {
    const res = await fetch(
      `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=5`,
      { headers: { "Accept-Language": "en" } }
    );
    const data = await res.json();
    searchResults.value = data;
    showDropdown.value = data.length > 0;
  } catch {
    searchResults.value = [];
  } finally {
    searching.value = false;
  }
}

function selectResult(result: { display_name: string; lat: string; lon: string }) {
  const lat = parseFloat(result.lat);
  const lng = parseFloat(result.lon);
  emit("update:latitude", lat);
  emit("update:longitude", lng);
  const shortName = result.display_name.split(",").slice(0, 2).join(",").trim();
  emit("update:locationName", shortName);
  updateMarkerAndCircle(lat, lng);
  map?.flyTo([lat, lng], 15);
  searchQuery.value = result.display_name;
  showDropdown.value = false;
}

function onRadiusInput(e: Event) {
  const val = parseInt((e.target as HTMLInputElement).value, 10);
  if (!isNaN(val) && val >= 10) {
    emit("update:radiusMeters", val);
  }
}

function onLatInput(e: Event) {
  const val = parseFloat((e.target as HTMLInputElement).value);
  if (!isNaN(val) && val >= -90 && val <= 90 && props.longitude !== null) {
    emit("update:latitude", val);
    updateMarkerAndCircle(val, props.longitude);
    map?.panTo([val, props.longitude]);
  }
}

function onLngInput(e: Event) {
  const val = parseFloat((e.target as HTMLInputElement).value);
  if (!isNaN(val) && val >= -180 && val <= 180 && props.latitude !== null) {
    emit("update:longitude", val);
    updateMarkerAndCircle(props.latitude, val);
    map?.panTo([props.latitude, val]);
  }
}
</script>

<template>
  <div class="space-y-2">
    <!-- Search bar -->
    <div class="relative">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search location…"
        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
        @input="onSearchInput"
        @blur="onSearchBlur"
      />
      <div
        v-if="showDropdown && searchResults.length"
        class="absolute z-[1000] mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg"
      >
        <button
          v-for="(result, i) in searchResults"
          :key="i"
          type="button"
          class="w-full truncate px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
          @mousedown.prevent="selectResult(result)"
        >
          {{ result.display_name }}
        </button>
      </div>
    </div>

    <!-- Map -->
    <div ref="mapRef" class="h-80 w-full rounded-lg border border-slate-200 overflow-hidden" />

    <!-- Coordinates + Radius inputs -->
    <div class="grid grid-cols-3 gap-2">
      <div class="space-y-1">
        <label class="text-xs font-medium text-slate-600">Latitude</label>
        <input
          :value="latitude !== null ? latitude : ''"
          type="number"
          step="0.000001"
          min="-90"
          max="90"
          placeholder="e.g. 3.1390"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
          @change="onLatInput"
        />
      </div>
      <div class="space-y-1">
        <label class="text-xs font-medium text-slate-600">Longitude</label>
        <input
          :value="longitude !== null ? longitude : ''"
          type="number"
          step="0.000001"
          min="-180"
          max="180"
          placeholder="e.g. 101.6869"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
          @change="onLngInput"
        />
      </div>
      <div class="space-y-1">
        <label class="text-xs font-medium text-slate-600">Radius (meters)</label>
        <input
          :value="radiusMeters"
          type="number"
          min="10"
          max="50000"
          step="10"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
          @input="onRadiusInput"
        />
      </div>
    </div>
    <p class="text-xs text-slate-400">Drag pin, click map, search, or type coordinates directly.</p>
  </div>
</template>
