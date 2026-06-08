<script setup lang="ts">
import { computed, ref, watch, onMounted } from "vue";
import { useRouter } from "vue-router";
import { Users, Search, ChevronLeft, ChevronRight, Building2, UserCheck, Briefcase, BadgeCheck } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AppSelect from "@/components/AppSelect.vue";
import AppTooltip from "@/components/AppTooltip.vue";
import { useToast } from "@/composables/useToast";
import { listStaff, listDivisions, listOffices, listJobStatuses, listJobTitles, getStaffStats } from "@/api/cms";
import { API_BASE_URL } from "@/env";
import type { StaffMember, Division, Office, JobStatus, JobTitle } from "@/types";

const router = useRouter();
const toast = useToast();

const staff = ref<StaffMember[]>([]);
const divisions = ref<Division[]>([]);
const offices = ref<Office[]>([]);
const supervisors = ref<StaffMember[]>([]);
const jobStatuses = ref<JobStatus[]>([]);
const jobTitles = ref<JobTitle[]>([]);
const stats = ref({ total: 0, withDivision: 0, withSupervisor: 0, withJobTitle: 0, withJobStatus: 0 });
const loading = ref(false);
const page = ref(1);
const total = ref(0);
const totalPages = ref(1);
const LIMIT = 20;

// Filter state
const searchQ = ref("");
const filterJobStatusId = ref<string | number | null>(null);
const filterJobTitleId = ref<string | number | null>(null);
const filterDivisionId = ref<string | number | null>(null);
const filterOfficeId = ref<string | number | null>(null);
const filterSupervisorId = ref<string | number | null>(null);

const jobStatusOptions = computed(() => jobStatuses.value.map((s) => ({ value: s.id, label: s.name })));
const jobTitleOptions = computed(() => jobTitles.value.map((t) => ({ value: t.id, label: t.name })));
const divisionOptions = computed(() => divisions.value.map((d) => ({ value: d.id, label: d.name })));
const officeOptions = computed(() => offices.value.map((o) => ({ value: o.id, label: o.name })));
const supervisorOptions = computed(() => supervisors.value.map((s) => ({ value: s.id, label: s.name })));

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    params.set("page", String(page.value));
    params.set("limit", String(LIMIT));
    if (searchQ.value) params.set("q", searchQ.value);
    if (filterJobStatusId.value !== null) params.set("job_status", String(filterJobStatusId.value));
    if (filterJobTitleId.value !== null) params.set("job_title", String(filterJobTitleId.value));
    if (filterDivisionId.value !== null) params.set("division_id", String(filterDivisionId.value));
    if (filterOfficeId.value !== null) params.set("office_id", String(filterOfficeId.value));
    if (filterSupervisorId.value !== null) params.set("supervisor_id", String(filterSupervisorId.value));

    const res = await listStaff(`?${params}`);
    staff.value = res.data;
    total.value = Number(res.meta?.total ?? 0);
    totalPages.value = Number(res.meta?.totalPages ?? 1);
  } catch (e) {
    toast.error("Failed to load employees", e instanceof Error ? e.message : "");
  } finally {
    loading.value = false;
  }
}

async function loadMeta() {
  const [divRes, offRes, staffRes, jsRes, jtRes, statsRes] = await Promise.all([
    listDivisions("?limit=500"),
    listOffices("?limit=200"),
    listStaff("?limit=500"),
    listJobStatuses("?limit=500"),
    listJobTitles("?limit=500"),
    getStaffStats(),
  ]);
  divisions.value = divRes.data;
  offices.value = offRes.data;
  supervisors.value = staffRes.data;
  jobStatuses.value = jsRes.data;
  jobTitles.value = jtRes.data;
  stats.value = statsRes.data;
}

let searchTimer: ReturnType<typeof setTimeout> | null = null;

function onSearchInput() {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { page.value = 1; load(); }, 350);
}

watch([filterJobStatusId, filterJobTitleId, filterDivisionId, filterOfficeId, filterSupervisorId], () => {
  page.value = 1;
  load();
});

function prevPage() {
  if (page.value > 1) { page.value--; load(); }
}

function nextPage() {
  if (page.value < totalPages.value) { page.value++; load(); }
}

function resolveUrl(url: string | null): string {
  if (!url) return "";
  if (url.startsWith("http")) return url;
  return `${API_BASE_URL}${url}`;
}

function initials(name: string): string {
  return name.split(" ").map((n) => n[0]).join("").toUpperCase().slice(0, 2);
}

onMounted(() => { load(); loadMeta(); });
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <AppBreadcrumb :items="[{ label: 'Human Resources' }, { label: 'Employees' }]" />

      <!-- Header -->
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="page-title">Employees</h1>
          <p class="mt-0.5 text-sm text-slate-500">View and manage employee profiles, division assignments, and supervisors.</p>
        </div>
        <div class="relative shrink-0 w-60">
          <Search class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
          <input
            v-model="searchQ"
            type="text"
            placeholder="Search name or email…"
            class="w-full rounded-lg border border-slate-300 py-1.5 pl-8 pr-3 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
            @input="onSearchInput"
          />
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wider text-slate-500">Total Employees</span>
            <Users class="h-4 w-4 text-violet-500" />
          </div>
          <p class="mt-2 text-2xl font-bold text-slate-900">{{ stats.total }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wider text-slate-500">In Division</span>
            <Building2 class="h-4 w-4 text-indigo-500" />
          </div>
          <p class="mt-2 text-2xl font-bold text-slate-900">{{ stats.withDivision }}</p>
          <p class="text-xs text-slate-400">{{ stats.total ? Math.round(stats.withDivision / stats.total * 100) : 0 }}% assigned</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wider text-slate-500">Have Supervisor</span>
            <UserCheck class="h-4 w-4 text-emerald-500" />
          </div>
          <p class="mt-2 text-2xl font-bold text-slate-900">{{ stats.withSupervisor }}</p>
          <p class="text-xs text-slate-400">{{ stats.total ? Math.round(stats.withSupervisor / stats.total * 100) : 0 }}% reporting</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wider text-slate-500">Job Title Set</span>
            <Briefcase class="h-4 w-4 text-amber-500" />
          </div>
          <p class="mt-2 text-2xl font-bold text-slate-900">{{ stats.withJobTitle }}</p>
          <p class="text-xs text-slate-400">{{ stats.total ? Math.round(stats.withJobTitle / stats.total * 100) : 0 }}% classified</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="grid grid-cols-2 gap-2 lg:grid-cols-5">
        <AppSelect v-model="filterJobStatusId" :options="jobStatusOptions" placeholder="Job Status" />
        <AppSelect v-model="filterJobTitleId" :options="jobTitleOptions" placeholder="Job Title" />
        <AppSelect v-model="filterDivisionId" :options="divisionOptions" placeholder="Division" />
        <AppSelect v-model="filterOfficeId" :options="officeOptions" placeholder="Office" />
        <AppSelect v-model="filterSupervisorId" :options="supervisorOptions" placeholder="Supervisor" />
      </div>

      <!-- Table -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
          <Users class="h-4 w-4 text-violet-600" />
          <h2 class="text-sm font-semibold text-slate-900">All Employees</h2>
          <span class="ml-auto text-xs text-slate-400">{{ total }} total</span>
        </div>

        <div v-if="loading" class="px-4 py-8 text-center text-sm text-slate-400">Loading…</div>
        <div v-else-if="staff.length === 0" class="px-4 py-8 text-center text-sm text-slate-400">No employees found.</div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Job Title</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Division</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Office</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Supervisor</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="member in staff"
                :key="member.id"
                class="cursor-pointer transition-colors hover:bg-slate-50"
                @click="router.push(`/admin/hr/staff/${member.id}/edit`)"
              >
                <td class="px-4 py-2.5">
                  <div class="flex items-center gap-2.5">
                    <img
                      v-if="member.photoUrl"
                      :src="resolveUrl(member.photoUrl)"
                      :alt="member.name"
                      class="h-7 w-7 shrink-0 rounded-full object-cover"
                    />
                    <div
                      v-else
                      class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-indigo-500 text-[10px] font-semibold text-white"
                    >
                      {{ initials(member.name) }}
                    </div>
                    <span class="font-medium text-slate-900">{{ member.name }}</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ member.role }}</span>
                  </div>
                </td>
                <td class="px-4 py-2.5 text-xs text-slate-500">{{ member.jobTitleName ?? "—" }}</td>
                <td class="px-4 py-2.5">
                  <span v-if="member.jobStatusName" class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">
                    {{ member.jobStatusName }}
                  </span>
                  <span v-else class="text-xs text-slate-400">—</span>
                </td>
                <td class="px-4 py-2.5">
                  <span v-if="member.divisionName" class="rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-medium text-violet-700">
                    {{ member.divisionName }}
                  </span>
                  <span v-else class="text-xs text-slate-400">—</span>
                </td>
                <td class="px-4 py-2.5 text-xs text-slate-500">{{ member.officeName ?? "—" }}</td>
                <td class="px-4 py-2.5">
                  <AppTooltip v-if="member.supervisorName" :text="member.supervisorName">
                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-slate-400 to-slate-600 text-[10px] font-semibold text-white cursor-default">
                      {{ initials(member.supervisorName) }}
                    </div>
                  </AppTooltip>
                  <span v-else class="text-xs text-slate-400">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-2.5">
          <span class="text-xs text-slate-400">Page {{ page }} of {{ totalPages }}</span>
          <div class="flex items-center gap-1">
            <button
              class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 disabled:opacity-40"
              :disabled="page <= 1"
              @click="prevPage"
            >
              <ChevronLeft class="h-4 w-4" />
            </button>
            <button
              v-for="p in totalPages"
              :key="p"
              class="flex h-7 w-7 items-center justify-center rounded-md text-xs font-medium transition-colors"
              :class="p === page ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-100'"
              @click="page = p; load()"
            >
              {{ p }}
            </button>
            <button
              class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 disabled:opacity-40"
              :disabled="page >= totalPages"
              @click="nextPage"
            >
              <ChevronRight class="h-4 w-4" />
            </button>
          </div>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
