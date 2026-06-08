<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { MapPin, Plus, Pencil, Trash2 } from "lucide-vue-next";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { useToast } from "@/composables/useToast";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { listOffices, deleteOffice } from "@/api/cms";
import type { Office } from "@/types";

const router = useRouter();
const toast = useToast();
const confirmDialog = useConfirmDialog();
const offices = ref<Office[]>([]);
const loading = ref(false);

async function load() {
  loading.value = true;
  try {
    const res = await listOffices("?limit=200");
    offices.value = res.data;
  } catch (e) {
    toast.error("Failed to load offices", e instanceof Error ? e.message : "");
  } finally {
    loading.value = false;
  }
}

async function remove(office: Office) {
  const ok = await confirmDialog.confirm({
    title: "Delete office location?",
    message: `"${office.name}" will be permanently removed.`,
    confirmText: "Delete",
    destructive: true,
  });
  if (!ok) return;
  try {
    await deleteOffice(office.id);
    toast.success("Office location deleted");
    await load();
  } catch (e) {
    toast.error("Delete failed", e instanceof Error ? e.message : "");
  }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <AppBreadcrumb :items="[{ label: 'Attendance' }, { label: 'Management' }, { label: 'Office Locations' }]" />

      <!-- Header -->
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="page-title">Office Locations</h1>
          <p class="mt-0.5 text-sm text-slate-500">
            Physical office locations with geofence boundaries. Each location is linked to a
            <router-link to="/admin/attendance/policies" class="font-medium text-violet-600 hover:underline">Work Policy</router-link>
            to enforce check-in radius.
          </p>
        </div>
        <button
          class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-1.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800"
          @click="router.push('/admin/attendance/offices/new')"
        >
          <Plus class="h-4 w-4" />
          New Location
        </button>
      </div>

      <!-- List -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
          <MapPin class="h-4 w-4 text-violet-600" />
          <h2 class="text-sm font-semibold text-slate-900">All Office Locations</h2>
          <span class="ml-auto text-xs text-slate-400">{{ offices.length }} total</span>
        </div>

        <div v-if="loading" class="px-4 py-8 text-center text-sm text-slate-400">Loading…</div>
        <div v-else-if="offices.length === 0" class="px-4 py-8 text-center text-sm text-slate-400">No office locations yet. Create one to get started.</div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Policy</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Coordinates</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Radius</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="office in offices" :key="office.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2.5 font-medium text-slate-900">{{ office.name }}</td>
                <td class="px-4 py-2.5">
                  <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">
                    {{ office.policyName ?? `Policy #${office.policyId}` }}
                  </span>
                </td>
                <td class="px-4 py-2.5 font-mono text-xs text-slate-500">
                  {{ office.latitude.toFixed(5) }}, {{ office.longitude.toFixed(5) }}
                </td>
                <td class="px-4 py-2.5 text-xs text-slate-500">{{ office.radiusMeters }}m</td>
                <td class="px-4 py-2.5 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      title="Edit"
                      @click="router.push(`/admin/attendance/offices/${office.id}/edit`)"
                    >
                      <Pencil class="h-3.5 w-3.5" />
                    </button>
                    <button
                      class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                      title="Delete"
                      @click="remove(office)"
                    >
                      <Trash2 class="h-3.5 w-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
