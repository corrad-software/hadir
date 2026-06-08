<script setup lang="ts">
import { ref, onMounted } from "vue";
import { Briefcase, Plus, Check, X, Pencil, Trash2, Search, ChevronLeft, ChevronRight } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import { useToast } from "@/composables/useToast";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { listJobStatuses, createJobStatus, updateJobStatus, deleteJobStatus } from "@/api/cms";
import type { JobStatus } from "@/types";

const toast = useToast();
const confirmDialog = useConfirmDialog();

const statuses = ref<JobStatus[]>([]);
const loading = ref(false);
const searchQ = ref("");
const page = ref(1);
const total = ref(0);
const totalPages = ref(1);
const LIMIT = 20;

const editingId = ref<number | null>(null);
const editName = ref("");
const addingNew = ref(false);
const newName = ref("");
const saving = ref(false);

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    params.set("page", String(page.value));
    params.set("limit", String(LIMIT));
    if (searchQ.value) params.set("q", searchQ.value);
    const res = await listJobStatuses(`?${params}`);
    statuses.value = res.data;
    total.value = Number(res.meta?.total ?? 0);
    totalPages.value = Number(res.meta?.totalPages ?? 1);
  } catch (e) {
    toast.error("Failed to load job statuses", e instanceof Error ? e.message : "");
  } finally {
    loading.value = false;
  }
}

let searchTimer: ReturnType<typeof setTimeout> | null = null;
function onSearchInput() {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { page.value = 1; load(); }, 350);
}

function prevPage() { if (page.value > 1) { page.value--; load(); } }
function nextPage() { if (page.value < totalPages.value) { page.value++; load(); } }

function startEdit(s: JobStatus) {
  editingId.value = s.id;
  editName.value = s.name;
  addingNew.value = false;
}

function cancelEdit() { editingId.value = null; editName.value = ""; }

async function saveEdit(id: number) {
  if (!editName.value.trim()) return;
  saving.value = true;
  try {
    await updateJobStatus(id, { name: editName.value.trim() });
    toast.success("Saved");
    cancelEdit();
    await load();
  } catch (e) {
    toast.error("Save failed", e instanceof Error ? e.message : "");
  } finally {
    saving.value = false;
  }
}

function startAdd() { addingNew.value = true; newName.value = ""; editingId.value = null; }
function cancelAdd() { addingNew.value = false; newName.value = ""; }

async function saveNew() {
  if (!newName.value.trim()) return;
  saving.value = true;
  try {
    await createJobStatus({ name: newName.value.trim() });
    toast.success("Job status added");
    cancelAdd();
    page.value = 1;
    await load();
  } catch (e) {
    toast.error("Create failed", e instanceof Error ? e.message : "");
  } finally {
    saving.value = false;
  }
}

async function remove(s: JobStatus) {
  const ok = await confirmDialog.confirm({
    title: "Delete job status?",
    message: `"${s.name}" will be permanently removed. Employees currently assigned this status will become unassigned.`,
    confirmText: "Delete",
    destructive: true,
  });
  if (!ok) return;
  try {
    await deleteJobStatus(s.id);
    toast.success("Deleted");
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
      <AppBreadcrumb :items="[
        { label: 'Human Resources' },
        { label: 'Configuration' },
        { label: 'Job Statuses' },
      ]" />

      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="page-title">Job Statuses</h1>
          <p class="mt-0.5 text-sm text-slate-500">Employment status options available when editing an employee profile.</p>
        </div>
        <div class="flex items-center gap-2">
          <div class="relative w-52">
            <Search class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
            <input
              v-model="searchQ"
              type="text"
              placeholder="Search…"
              class="w-full rounded-lg border border-slate-300 py-1.5 pl-8 pr-3 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              @input="onSearchInput"
            />
          </div>
          <button
            class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-1.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800"
            @click="startAdd"
          >
            <Plus class="h-4 w-4" />
            Add Status
          </button>
        </div>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
          <Briefcase class="h-4 w-4 text-amber-600" />
          <h2 class="text-sm font-semibold text-slate-900">All Job Statuses</h2>
          <span class="ml-auto text-xs text-slate-400">{{ total }} total</span>
        </div>

        <div v-if="loading" class="px-4 py-8 text-center text-sm text-slate-400">Loading…</div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="addingNew" class="bg-violet-50/40">
                <td class="px-4 py-2">
                  <input
                    v-model="newName"
                    type="text"
                    placeholder="e.g. Permanent"
                    autofocus
                    class="w-full rounded-lg border border-violet-300 px-2.5 py-1.5 text-sm focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-200"
                    @keyup.enter="saveNew"
                    @keyup.esc="cancelAdd"
                  />
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button class="flex h-8 w-8 items-center justify-center rounded-lg text-green-600 transition-colors hover:bg-green-50 disabled:opacity-40" :disabled="saving || !newName.trim()" @click="saveNew"><Check class="h-4 w-4" /></button>
                    <button class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100" @click="cancelAdd"><X class="h-4 w-4" /></button>
                  </div>
                </td>
              </tr>

              <tr v-if="statuses.length === 0 && !addingNew">
                <td colspan="2" class="px-4 py-8 text-center text-sm text-slate-400">No job statuses found.</td>
              </tr>

              <tr v-for="s in statuses" :key="s.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2.5">
                  <input
                    v-if="editingId === s.id"
                    v-model="editName"
                    type="text"
                    class="w-full rounded-lg border border-violet-300 px-2.5 py-1.5 text-sm focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-200"
                    @keyup.enter="saveEdit(s.id)"
                    @keyup.esc="cancelEdit"
                  />
                  <span v-else class="font-medium text-slate-900">{{ s.name }}</span>
                </td>
                <td class="px-4 py-2.5 text-right">
                  <div v-if="editingId === s.id" class="flex items-center justify-end gap-1">
                    <button class="flex h-8 w-8 items-center justify-center rounded-lg text-green-600 transition-colors hover:bg-green-50 disabled:opacity-40" :disabled="saving || !editName.trim()" @click="saveEdit(s.id)"><Check class="h-4 w-4" /></button>
                    <button class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100" @click="cancelEdit"><X class="h-4 w-4" /></button>
                  </div>
                  <div v-else class="flex items-center justify-end gap-1">
                    <button class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700" title="Edit" @click="startEdit(s)"><Pencil class="h-3.5 w-3.5" /></button>
                    <button class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600" title="Delete" @click="remove(s)"><Trash2 class="h-3.5 w-3.5" /></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-2.5">
          <span class="text-xs text-slate-400">Page {{ page }} of {{ totalPages }}</span>
          <div class="flex items-center gap-1">
            <button class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 disabled:opacity-40" :disabled="page <= 1" @click="prevPage"><ChevronLeft class="h-4 w-4" /></button>
            <button v-for="p in totalPages" :key="p" class="flex h-7 w-7 items-center justify-center rounded-md text-xs font-medium transition-colors" :class="p === page ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-100'" @click="page = p; load()">{{ p }}</button>
            <button class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 disabled:opacity-40" :disabled="page >= totalPages" @click="nextPage"><ChevronRight class="h-4 w-4" /></button>
          </div>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
