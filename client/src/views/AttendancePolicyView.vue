<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { Settings2, Plus, Pencil, Trash2, CheckCircle2 } from "lucide-vue-next";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { useToast } from "@/composables/useToast";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { listAttendancePolicies, deleteAttendancePolicy, activateAttendancePolicy } from "@/api/cms";
import type { AttendancePolicy } from "@/types";

const router = useRouter();
const toast = useToast();
const confirmDialog = useConfirmDialog();
const policies = ref<AttendancePolicy[]>([]);
const loading = ref(false);

const DAY_NAMES = ["", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];

async function load() {
  loading.value = true;
  try {
    const res = await listAttendancePolicies();
    policies.value = res.data;
  } catch (e) {
    toast.error("Failed to load policies", e instanceof Error ? e.message : "");
  } finally {
    loading.value = false;
  }
}

async function activate(id: number) {
  try {
    await activateAttendancePolicy(id);
    toast.success("Policy activated");
    await load();
  } catch (e) {
    toast.error("Activation failed", e instanceof Error ? e.message : "");
  }
}

async function remove(policy: AttendancePolicy) {
  const ok = await confirmDialog.confirm({
    title: "Delete policy?",
    message: `"${policy.name}" will be permanently removed.`,
    confirmText: "Delete",
    destructive: true,
  });
  if (!ok) return;
  try {
    await deleteAttendancePolicy(policy.id);
    toast.success("Policy deleted");
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
      <AppBreadcrumb :items="[{ label: 'Human Resources' }, { label: 'Configuration' }, { label: 'Work Policies' }]" />

      <!-- Header -->
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="page-title">Work Policies</h1>
          <p class="mt-0.5 text-sm text-slate-500">Define working hours, days, and grace periods for each division or office.</p>
        </div>
        <button
          class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-1.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800"
          @click="router.push('/admin/attendance/policies/new')"
        >
          <Plus class="h-4 w-4" />
          New Policy
        </button>
      </div>

      <!-- Table -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
          <Settings2 class="h-4 w-4 text-amber-600" />
          <h2 class="text-sm font-semibold text-slate-900">All Policies</h2>
          <span class="ml-auto text-xs text-slate-400">{{ policies.length }} total</span>
        </div>

        <div v-if="loading" class="px-4 py-8 text-center text-sm text-slate-400">Loading…</div>
        <div v-else-if="policies.length === 0" class="px-4 py-8 text-center text-sm text-slate-400">No policies yet. Create one to get started.</div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Work Days</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Hours</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Grace Period</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Offices</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="policy in policies"
                :key="policy.id"
                class="hover:bg-slate-50"
              >
                <td class="px-4 py-2.5">
                  <div class="flex items-center gap-2">
                    <span class="font-medium text-slate-900">{{ policy.name }}</span>
                    <span
                      v-if="policy.isActive"
                      class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700"
                    >
                      <CheckCircle2 class="h-3 w-3" /> Active
                    </span>
                  </div>
                </td>
                <td class="px-4 py-2.5 text-xs text-slate-600">
                  {{ policy.workDays.map(d => DAY_NAMES[d]).join(", ") }}
                </td>
                <td class="px-4 py-2.5 text-xs text-slate-600">
                  {{ policy.startTime }}–{{ policy.endTime }}
                </td>
                <td class="px-4 py-2.5">
                  <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                    {{ policy.gracePeriodMinutes }}min
                  </span>
                </td>
                <td class="px-4 py-2.5">
                  <span class="rounded-full bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700">
                    {{ policy.officesCount ?? 0 }} office(s)
                  </span>
                </td>
                <td class="px-4 py-2.5">
                  <div class="flex items-center justify-end gap-1">
                    <button
                      v-if="!policy.isActive"
                      class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50"
                      @click="activate(policy.id)"
                    >
                      Activate
                    </button>
                    <button
                      class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      @click="router.push(`/admin/attendance/policies/${policy.id}/edit`)"
                    >
                      <Pencil class="h-3.5 w-3.5" />
                    </button>
                    <button
                      class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                      @click="remove(policy)"
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
