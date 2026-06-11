<script setup lang="ts">
import { ref, onMounted, reactive, computed } from "vue";
import { Building2, Plus, Pencil, Trash2, Save, X, ChevronRight, ChevronDown, Search } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AppSelect from "@/components/AppSelect.vue";
import { useToast } from "@/composables/useToast";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import {
  listDivisions,
  createDivision,
  updateDivision,
  deleteDivision,
  listAttendancePolicies,
} from "@/api/cms";
import type { Division, DivisionInput, AttendancePolicy } from "@/types";

const toast = useToast();
const confirmDialog = useConfirmDialog();

const divisions = ref<Division[]>([]);
const policies = ref<AttendancePolicy[]>([]);
const loading = ref(false);
const saving = ref(false);
const showForm = ref(false);
const editingId = ref<number | null>(null);
const expandedIds = ref<Set<number>>(new Set());
const searchQ = ref("");

const form = reactive<DivisionInput>({
  name: "",
  parentId: null,
  attendancePolicyId: null,
});

function resetForm() {
  form.name = "";
  form.parentId = null;
  form.attendancePolicyId = null;
  editingId.value = null;
}

function openCreate() {
  resetForm();
  showForm.value = true;
}

function openEdit(div: Division) {
  form.name = div.name;
  form.parentId = div.parentId ?? null;
  form.attendancePolicyId = div.attendancePolicyId ?? null;
  editingId.value = div.id;
  showForm.value = true;
}

function cancelForm() {
  showForm.value = false;
  resetForm();
}

function toggleExpand(id: number) {
  if (expandedIds.value.has(id)) {
    expandedIds.value.delete(id);
  } else {
    expandedIds.value.add(id);
  }
}

async function load() {
  loading.value = true;
  try {
    const [divRes, polRes] = await Promise.all([
      listDivisions("?limit=200"),
      listAttendancePolicies(),
    ]);
    divisions.value = divRes.data;
    policies.value = polRes.data;
  } catch (e) {
    toast.error("Failed to load divisions", e instanceof Error ? e.message : "");
  } finally {
    loading.value = false;
  }
}

async function save() {
  saving.value = true;
  const wasEdit = editingId.value !== null;
  try {
    if (wasEdit && editingId.value !== null) {
      await updateDivision(editingId.value, form);
    } else {
      await createDivision(form);
    }
    toast.success(wasEdit ? "Division updated" : "Division created");
    showForm.value = false;
    resetForm();
    await load();
  } catch (e) {
    toast.error("Save failed", e instanceof Error ? e.message : "");
  } finally {
    saving.value = false;
  }
}

async function remove(div: Division) {
  if (div.childrenCount > 0 || div.usersCount > 0) {
    toast.error(
      "Cannot delete",
      div.childrenCount > 0
        ? `Division has ${div.childrenCount} sub-division(s)`
        : `Division has ${div.usersCount} staff member(s)`
    );
    return;
  }
  const ok = await confirmDialog.confirm({
    title: "Delete division?",
    message: `"${div.name}" will be permanently removed.`,
    confirmText: "Delete",
    destructive: true,
  });
  if (!ok) return;
  try {
    await deleteDivision(div.id);
    toast.success("Division deleted");
    await load();
  } catch (e) {
    toast.error("Delete failed", e instanceof Error ? e.message : "");
  }
}

type DivisionNode = Division & { children: DivisionNode[] };

const divisionTree = computed((): DivisionNode[] => {
  const map = new Map<number, DivisionNode>();
  const roots: DivisionNode[] = [];
  divisions.value.forEach((d) => map.set(d.id, { ...d, children: [] }));
  divisions.value.forEach((d) => {
    const node = map.get(d.id)!;
    if (d.parentId && map.has(d.parentId)) {
      map.get(d.parentId)!.children.push(node);
    } else {
      roots.push(node);
    }
  });
  return roots;
});

const filteredTree = computed((): DivisionNode[] => {
  const q = searchQ.value.toLowerCase().trim();
  if (!q) return divisionTree.value;
  return divisionTree.value.flatMap((root): DivisionNode[] => {
    const rootMatch = root.name.toLowerCase().includes(q);
    const matchingChildren = root.children.filter((c) => c.name.toLowerCase().includes(q));
    if (rootMatch) {
      return [{ ...root, children: matchingChildren.length ? matchingChildren : root.children }];
    }
    if (matchingChildren.length) {
      return [{ ...root, children: matchingChildren }];
    }
    return [];
  });
});

const parentOptions = computed(() =>
  divisions.value
    .filter((d) => d.id !== editingId.value)
    .map((d) => ({ value: d.id, label: d.name }))
);

const policyOptions = computed(() =>
  policies.value.map((p) => ({ value: p.id, label: p.name }))
);

function policyName(id: number | null): string {
  if (!id) return "—";
  return policies.value.find((p) => p.id === id)?.name ?? `Policy #${id}`;
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <AppBreadcrumb :items="[{ label: 'Human Resources' }, { label: 'Divisions' }]" />

      <!-- Header -->
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="page-title">Divisions</h1>
          <p class="mt-0.5 text-sm text-slate-500">Manage your organisational structure and assign attendance policies to each division.</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
          <div class="relative w-56">
            <Search class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
            <input
              v-model="searchQ"
              type="text"
              placeholder="Search divisions…"
              class="w-full rounded-lg border border-slate-300 py-1.5 pl-8 pr-3 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
            />
          </div>
          <button
            class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-1.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800"
            @click="openCreate"
          >
            <Plus class="h-4 w-4" />
            New Division
          </button>
        </div>
      </div>

      <!-- Form Card -->
      <article v-if="showForm" class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
          <Pencil class="h-4 w-4 text-violet-600" />
          <h2 class="text-sm font-semibold text-slate-900">{{ editingId ? "Edit Division" : "New Division" }}</h2>
        </div>
        <div class="space-y-4 p-4">
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Division Name</label>
            <input
              v-model="form.name"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              placeholder="e.g. Engineering, HR, Finance"
              @keyup.enter="save"
            />
          </div>

          <div class="grid gap-3 md:grid-cols-2">
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Parent Division <span class="font-normal text-slate-400">(optional)</span></label>
              <AppSelect
                v-model="form.parentId"
                :options="parentOptions"
                placeholder="— No parent (top-level) —"
              />
            </div>

            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Attendance Policy <span class="font-normal text-slate-400">(optional)</span></label>
              <AppSelect
                v-model="form.attendancePolicyId"
                :options="policyOptions"
                placeholder="— Use global active policy —"
              />
            </div>
          </div>

          <div class="flex items-center gap-3 border-t border-slate-100 pt-3">
            <button
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-50"
              :disabled="saving || !form.name"
              @click="save"
            >
              <Save class="h-4 w-4" />
              {{ saving ? "Saving…" : (editingId ? "Update" : "Create") }}
            </button>
            <button
              class="flex items-center gap-2 rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
              @click="cancelForm"
            >
              <X class="h-4 w-4" />
              Cancel
            </button>
          </div>
        </div>
      </article>

      <!-- Table -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
          <Building2 class="h-4 w-4 text-violet-600" />
          <h2 class="text-sm font-semibold text-slate-900">All Divisions</h2>
          <span class="ml-auto text-xs text-slate-400">{{ divisions.length }} total</span>
        </div>

        <div v-if="loading" class="px-4 py-8 text-center text-sm text-slate-400">Loading…</div>
        <div v-else-if="filteredTree.length === 0" class="px-4 py-8 text-center text-sm text-slate-400">
          {{ searchQ ? "No divisions match your search." : "No divisions yet. Create one to get started." }}
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Staff</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Sub-divisions</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Attendance Policy</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <template v-for="div in filteredTree" :key="div.id">
                <!-- Parent row -->
                <tr class="hover:bg-slate-50">
                  <td class="px-4 py-2.5">
                    <div class="flex items-center gap-2">
                      <button
                        v-if="div.childrenCount > 0"
                        type="button"
                        class="shrink-0 text-slate-400 hover:text-slate-600"
                        @click="toggleExpand(div.id)"
                      >
                        <ChevronDown v-if="expandedIds.has(div.id)" class="h-4 w-4" />
                        <ChevronRight v-else class="h-4 w-4" />
                      </button>
                      <div v-else class="h-4 w-4 shrink-0" />
                      <span class="font-medium text-slate-900">{{ div.name }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-2.5">
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ div.usersCount }}</span>
                  </td>
                  <td class="px-4 py-2.5 text-xs text-slate-500">
                    {{ div.childrenCount > 0 ? div.childrenCount : "—" }}
                  </td>
                  <td class="px-4 py-2.5">
                    <span v-if="div.attendancePolicyId" class="rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-medium text-violet-700">
                      {{ policyName(div.attendancePolicyId) }}
                    </span>
                    <span v-else class="text-xs text-slate-400">—</span>
                  </td>
                  <td class="px-4 py-2.5">
                    <div class="flex items-center justify-end gap-1">
                      <button
                        class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                        @click="openEdit(div)"
                      >
                        <Pencil class="h-3.5 w-3.5" />
                      </button>
                      <button
                        class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30"
                        :disabled="div.childrenCount > 0 || div.usersCount > 0"
                        @click="remove(div)"
                      >
                        <Trash2 class="h-3.5 w-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- Child rows -->
                <template v-if="expandedIds.has(div.id) && div.children.length">
                  <tr
                    v-for="child in div.children"
                    :key="child.id"
                    class="bg-slate-50/60 hover:bg-slate-50"
                  >
                    <td class="py-2.5 pl-12 pr-4">
                      <div class="flex items-center gap-2">
                        <span class="text-slate-400">↳</span>
                        <span class="font-medium text-slate-700">{{ child.name }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-2.5">
                      <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ child.usersCount }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-xs text-slate-400">—</td>
                    <td class="px-4 py-2.5">
                      <span v-if="child.attendancePolicyId" class="rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-medium text-violet-700">
                        {{ policyName(child.attendancePolicyId) }}
                      </span>
                      <span v-else class="text-xs text-slate-400">—</span>
                    </td>
                    <td class="px-4 py-2.5">
                      <div class="flex items-center justify-end gap-1">
                        <button
                          class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                          @click="openEdit(child)"
                        >
                          <Pencil class="h-3.5 w-3.5" />
                        </button>
                        <button
                          class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30"
                          :disabled="child.childrenCount > 0 || child.usersCount > 0"
                          @click="remove(child)"
                        >
                          <Trash2 class="h-3.5 w-3.5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>
              </template>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
