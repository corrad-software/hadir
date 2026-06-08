<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Save, ArrowLeft } from "lucide-vue-next";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { useToast } from "@/composables/useToast";
import {
  getAttendancePolicy,
  createAttendancePolicy,
  updateAttendancePolicy,
} from "@/api/cms";
import type { AttendancePolicyInput } from "@/types";

const route = useRoute();
const router = useRouter();
const toast = useToast();

const isNew = computed(() => !route.params.id);
const policyId = computed(() => (isNew.value ? null : Number(route.params.id)));

const loading = ref(false);
const saving = ref(false);

const DAY_NAMES = ["", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];

const form = reactive<AttendancePolicyInput>({
  name: "",
  work_days: [1, 2, 3, 4, 5],
  start_time: "08:00",
  end_time: "17:00",
  grace_period_minutes: 15,
  is_active: false,
});

function toggleDay(day: number) {
  if (form.work_days.includes(day)) {
    form.work_days = form.work_days.filter((d) => d !== day);
  } else {
    form.work_days = [...form.work_days, day].sort();
  }
}

async function load() {
  if (isNew.value || !policyId.value) return;
  loading.value = true;
  try {
    const res = await getAttendancePolicy(policyId.value);
    const p = res.data;
    form.name = p.name;
    form.work_days = [...p.workDays];
    form.start_time = p.startTime;
    form.end_time = p.endTime;
    form.grace_period_minutes = p.gracePeriodMinutes;
    form.is_active = p.isActive;
  } catch (e) {
    toast.error("Failed to load policy", e instanceof Error ? e.message : "");
    router.replace("/admin/attendance/policies");
  } finally {
    loading.value = false;
  }
}

async function save() {
  if (!form.name) return;
  saving.value = true;
  try {
    if (isNew.value) {
      await createAttendancePolicy(form);
      toast.success("Policy created");
    } else {
      await updateAttendancePolicy(policyId.value!, form);
      toast.success("Policy updated");
    }
    router.push("/admin/attendance/policies");
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
      <AppBreadcrumb :items="[{ label: 'Attendance' }, { label: 'Work Policies', to: '/admin/attendance/policies' }, { label: isNew ? 'New Policy' : 'Edit Policy' }]" />

      <!-- Header -->
      <div class="flex items-center gap-3">
        <router-link
          to="/admin/attendance/policies"
          class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
        >
          <ArrowLeft class="h-4 w-4" />
        </router-link>
        <h1 class="page-title">{{ isNew ? 'New Policy' : 'Edit Policy' }}</h1>
      </div>

      <!-- Form -->
      <div v-if="loading" class="rounded-lg border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-400 shadow-sm">Loading…</div>

      <article v-else class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="space-y-4 p-6">
          <!-- Name -->
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Policy Name</label>
            <input
              v-model="form.name"
              type="text"
              placeholder="e.g. Default Policy"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
            />
          </div>

          <!-- Work Days -->
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Work Days</label>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="d in [1,2,3,4,5,6,7]"
                :key="d"
                type="button"
                class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                :class="form.work_days.includes(d) ? 'bg-violet-100 text-violet-700 ring-1 ring-violet-300' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                @click="toggleDay(d)"
              >
                {{ DAY_NAMES[d] }}
              </button>
            </div>
          </div>

          <!-- Hours -->
          <div class="grid gap-3 sm:grid-cols-3">
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Start Time</label>
              <input
                v-model="form.start_time"
                type="time"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">End Time</label>
              <input
                v-model="form.end_time"
                type="time"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Grace Period (min)</label>
              <input
                v-model.number="form.grace_period_minutes"
                type="number"
                min="0"
                max="120"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>
          </div>

          <!-- Active -->
          <label class="flex items-center gap-2.5 text-sm text-slate-700">
            <input v-model="form.is_active" type="checkbox" class="rounded" />
            Set as active policy immediately
          </label>

          <!-- Actions -->
          <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
            <button
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-50"
              :disabled="saving || !form.name"
              @click="save"
            >
              <Save class="h-4 w-4" />
              {{ saving ? 'Saving…' : (isNew ? 'Create Policy' : 'Save Changes') }}
            </button>
            <router-link
              to="/admin/attendance/policies"
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
