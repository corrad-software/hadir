<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Save, ArrowLeft } from "lucide-vue-next";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import GeofenceMapPicker from "@/components/GeofenceMapPicker.vue";
import { useToast } from "@/composables/useToast";
import {
  getOffice,
  createOffice,
  updateOffice,
  listAttendancePolicies,
} from "@/api/cms";
import type { AttendancePolicy, OfficeInput } from "@/types";

const route = useRoute();
const router = useRouter();
const toast = useToast();

const isNew = computed(() => !route.params.id);
const officeId = computed(() => (isNew.value ? null : Number(route.params.id)));

const loading = ref(false);
const saving = ref(false);
const policies = ref<AttendancePolicy[]>([]);

const form = reactive<OfficeInput>({
  name: "",
  latitude: null,
  longitude: null,
  radiusMeters: 200,
  policyId: null,
});

async function load() {
  try {
    const res = await listAttendancePolicies();
    policies.value = res.data;
  } catch {
    toast.error("Failed to load policies");
  }

  if (isNew.value || !officeId.value) return;
  loading.value = true;
  try {
    const res = await getOffice(officeId.value);
    const o = res.data;
    form.name = o.name;
    form.latitude = o.latitude;
    form.longitude = o.longitude;
    form.radiusMeters = o.radiusMeters;
    form.policyId = o.policyId;
  } catch (e) {
    toast.error("Failed to load office location", e instanceof Error ? e.message : "");
    router.replace("/admin/attendance/offices");
  } finally {
    loading.value = false;
  }
}

async function save() {
  if (!form.name) { toast.error("Validation", "Please enter an office name."); return; }
  if (!form.policyId) { toast.error("Validation", "Please select a policy."); return; }
  if (form.latitude === null || form.longitude === null) {
    toast.error("Validation", "Please set a location on the map.");
    return;
  }
  saving.value = true;
  try {
    if (isNew.value) {
      await createOffice(form);
      toast.success("Office location created");
    } else {
      await updateOffice(officeId.value!, form);
      toast.success("Office location updated");
    }
    router.push("/admin/attendance/offices");
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
      <AppBreadcrumb :items="[{ label: 'Attendance' }, { label: 'Office Locations', to: '/admin/attendance/offices' }, { label: isNew ? 'New Location' : 'Edit Location' }]" />

      <!-- Header -->
      <div class="flex items-center gap-3">
        <router-link
          to="/admin/attendance/offices"
          class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
        >
          <ArrowLeft class="h-4 w-4" />
        </router-link>
        <h1 class="page-title">{{ isNew ? 'New Office Location' : 'Edit Office Location' }}</h1>
      </div>

      <div v-if="loading" class="rounded-lg border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-400 shadow-sm">Loading…</div>

      <article v-else class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="space-y-5 p-6">
          <!-- Name + Policy -->
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Office Name</label>
              <input
                v-model="form.name"
                type="text"
                placeholder="e.g. HQ Kuala Lumpur"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Attendance Policy</label>
              <select
                v-model="form.policyId"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              >
                <option :value="null">— Select a policy —</option>
                <option v-for="p in policies" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
          </div>

          <!-- Map -->
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">
              Location & Geofence
              <span class="font-normal text-slate-400 ml-1">(search, click map, or enter coordinates)</span>
            </label>
            <GeofenceMapPicker
              :latitude="form.latitude"
              :longitude="form.longitude"
              :radius-meters="form.radiusMeters"
              :location-name="form.name"
              @update:latitude="form.latitude = $event"
              @update:longitude="form.longitude = $event"
              @update:radius-meters="form.radiusMeters = $event"
              @update:location-name="form.name = $event"
            />
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
            <button
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-50"
              :disabled="saving || !form.name || !form.policyId"
              @click="save"
            >
              <Save class="h-4 w-4" />
              {{ saving ? 'Saving…' : (isNew ? 'Create Location' : 'Save Changes') }}
            </button>
            <router-link
              to="/admin/attendance/offices"
              class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
            >
              Cancel
            </router-link>
          </div>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
