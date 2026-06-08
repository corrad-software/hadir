<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import {
  Users,
  Plus,
  Pencil,
  Trash2,
  CheckCircle2,
  XCircle,
  Search,
  Building2,
  RefreshCw,
  ChevronLeft,
  ChevronRight,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import { listUsers, deleteUser, listDivisions, syncUsersFromSso } from "@/api/cms";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import type { Division, UserDetail } from "@/types";

const users = ref<UserDetail[]>([]);
const divisions = ref<Division[]>([]);
const confirmDialog = useConfirmDialog();
const toast = useToast();

const page = ref(1);
const limit = 15;
const total = ref(0);
const totalPages = ref(0);
const searchQuery = ref("");
const filterDivision = ref<number | "">("");
const loading = ref(false);
const syncing = ref(false);

let searchTimer: ReturnType<typeof setTimeout> | null = null;

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams({
      page: String(page.value),
      limit: String(limit),
    });
    if (searchQuery.value.trim()) params.set("q", searchQuery.value.trim());
    if (filterDivision.value !== "") params.set("division_id", String(filterDivision.value));

    const res = await listUsers(`?${params}`);
    users.value = res.data;
    total.value = Number(res.meta?.total ?? 0);
    totalPages.value = Number(res.meta?.totalPages ?? 1);
  } finally {
    loading.value = false;
  }
}

async function loadDivisions() {
  const res = await listDivisions("?limit=200");
  divisions.value = res.data;
}

function onSearchInput() {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { page.value = 1; load(); }, 350);
}

function onFilterChange() {
  page.value = 1;
  load();
}

function prevPage() { if (page.value > 1) { page.value--; load(); } }
function nextPage() { if (page.value < totalPages.value) { page.value++; load(); } }

async function syncFromSso() {
  syncing.value = true;
  try {
    const res = await syncUsersFromSso();
    const { created, updated } = res.data;
    toast.success(`SSO sync complete — ${created} added, ${updated} updated`);
    page.value = 1;
    await load();
  } catch (e) {
    toast.error("Sync failed", e instanceof Error ? e.message : "Could not reach SSO.");
  } finally {
    syncing.value = false;
  }
}

async function remove(id: number) {
  const allowed = await confirmDialog.confirm({
    title: "Delete user?",
    message: "This action cannot be undone.",
    confirmText: "Delete",
    destructive: true,
  });
  if (!allowed) return;
  try {
    await deleteUser(id);
    await load();
    toast.success("User deleted");
  } catch (e) {
    toast.error("Delete failed", e instanceof Error ? e.message : "Unable to delete user.");
  }
}

onMounted(() => { load(); loadDivisions(); });
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <!-- ───── Header ───── -->
      <div class="flex items-center justify-between">
        <h1 class="page-title">Users</h1>
        <div class="flex items-center gap-2">
          <button
            class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-50"
            :disabled="syncing"
            @click="syncFromSso"
          >
            <RefreshCw class="h-3.5 w-3.5" :class="syncing ? 'animate-spin' : ''" />
            {{ syncing ? "Syncing…" : "Sync from SSO" }}
          </button>
          <router-link
            to="/admin/platform/identity/users/new"
            class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-1.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800"
          >
            <Plus class="h-4 w-4" />
            Add User
          </router-link>
        </div>
      </div>

      <!-- ───── Filters ───── -->
      <div class="flex flex-wrap gap-2">
        <div class="relative flex-1 min-w-48">
          <Search class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search name or email…"
            class="w-full rounded-lg border border-slate-300 py-2 pl-8 pr-3 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
          @input="onSearchInput"
          />
        </div>
        <select
          v-model="filterDivision"
          class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
          @change="onFilterChange"
        >
          <option value="">All Divisions</option>
          <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
      </div>

      <!-- ───── Users Table ───── -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
          <Users class="h-4 w-4 text-blue-600" />
          <h2 class="text-sm font-semibold text-slate-900">All Users</h2>
          <span class="ml-auto text-xs text-slate-400">{{ total }} total</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Email</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Division</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Supervisor</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Role</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="loading">
                <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-400">Loading…</td>
              </tr>
              <template v-else>
                <tr v-for="user in users" :key="user.id" class="transition-colors hover:bg-slate-50">
                  <td class="px-4 py-2.5">
                    <div class="flex items-center gap-2.5">
                      <img
                        v-if="user.photoUrl"
                        :src="user.photoUrl"
                        :alt="user.name"
                        class="h-7 w-7 rounded-full object-cover shrink-0"
                      />
                      <div v-else class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-indigo-500 text-xs font-semibold text-white">
                        {{ user.name.charAt(0).toUpperCase() }}
                      </div>
                      <router-link :to="'/admin/platform/identity/users/' + user.id" class="font-medium text-slate-900 hover:text-violet-600">{{ user.name }}</router-link>
                    </div>
                  </td>
                  <td class="px-4 py-2.5 text-slate-500">{{ user.email }}</td>
                  <td class="px-4 py-2.5">
                    <span v-if="user.divisionName" class="inline-flex items-center gap-1 text-xs text-slate-600">
                      <Building2 class="h-3 w-3 text-slate-400" />{{ user.divisionName }}
                    </span>
                    <span v-else class="text-xs text-slate-300">—</span>
                  </td>
                  <td class="px-4 py-2.5 text-xs text-slate-500">
                    {{ user.supervisorName ?? "—" }}
                  </td>
                  <td class="px-4 py-2.5">
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ user.role }}</span>
                  </td>
                  <td class="px-4 py-2.5">
                    <span v-if="user.isActive" class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                      <CheckCircle2 class="h-3 w-3" /> Active
                    </span>
                    <span v-else class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">
                      <XCircle class="h-3 w-3" /> Inactive
                    </span>
                  </td>
                  <td class="px-4 py-2.5 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                      <router-link :to="'/admin/platform/identity/users/' + user.id" class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                        <Pencil class="h-3.5 w-3.5" />
                        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Edit</span>
                      </router-link>
                      <button class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600" @click="remove(user.id)">
                        <Trash2 class="h-3.5 w-3.5" />
                        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Delete</span>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="users.length === 0">
                  <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-400">No users found.</td>
                </tr>
              </template>
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
