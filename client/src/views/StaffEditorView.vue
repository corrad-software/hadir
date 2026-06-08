<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ArrowLeft, User, Briefcase, Building2, ArrowRightLeft, Save, Camera, Upload, Trash2 } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import AppBreadcrumb from "@/components/AppBreadcrumb.vue";
import AppSelect from "@/components/AppSelect.vue";
import { useToast } from "@/composables/useToast";
import { getStaff, updateStaff, uploadStaffPhoto, removeStaffPhoto, listOffices, listDivisions, listStaff, listJobStatuses, listJobTitles } from "@/api/cms";
import { API_BASE_URL } from "@/env";
import type { StaffMember, Division, Office, JobStatus, JobTitle, StaffUpdateInput } from "@/types";

const route = useRoute();
const router = useRouter();
const toast = useToast();

const staffId = computed(() => Number(route.params.id));

const loading = ref(true);
const saving = ref(false);
const uploadingPhoto = ref(false);
const photoUrl = ref<string | null>(null);
const member = ref<StaffMember | null>(null);
const offices = ref<Office[]>([]);
const divisions = ref<Division[]>([]);
const allStaff = ref<StaffMember[]>([]);
const jobStatuses = ref<JobStatus[]>([]);
const jobTitles = ref<JobTitle[]>([]);

const form = reactive<StaffUpdateInput>({
  name: "",
  dob: null,
  phone: null,
  sex: null,
  jobTitleId: null,
  jobStatusId: null,
  addressLine1: null,
  addressLine2: null,
  addressTownship: null,
  addressPostcode: null,
  addressState: null,
  officeId: null,
  divisionId: null,
  supervisorId: null,
});

const originalDivisionId = ref<number | null>(null);
const divisionChanged = computed(() => form.divisionId !== originalDivisionId.value);

const jobStatusOptions = computed(() => jobStatuses.value.map((s) => ({ value: s.id, label: s.name })));
const jobTitleOptions = computed(() => jobTitles.value.map((t) => ({ value: t.id, label: t.name })));

const MY_STATE_OPTIONS = [
  "Johor", "Kedah", "Kelantan", "Melaka", "Negeri Sembilan",
  "Pahang", "Perak", "Perlis", "Pulau Pinang", "Sabah",
  "Sarawak", "Selangor", "Terengganu", "WP Kuala Lumpur",
  "WP Labuan", "WP Putrajaya",
].map((s) => ({ value: s, label: s }));

const officeOptions = computed(() =>
  offices.value.map((o) => ({ value: o.id as number, label: o.name }))
);
const divisionOptions = computed(() =>
  divisions.value.map((d) => ({ value: d.id as number, label: d.name }))
);
const supervisorOptions = computed(() =>
  allStaff.value
    .filter((s) => s.id !== staffId.value)
    .map((s) => ({ value: s.id as number, label: s.name }))
);

function transferEffectiveDate(): string {
  const d = new Date();
  d.setDate(d.getDate() + 1);
  return d.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "numeric" });
}

async function load() {
  loading.value = true;
  try {
    const [staffRes, officeRes, divRes, allStaffRes, jsRes, jtRes] = await Promise.all([
      getStaff(staffId.value),
      listOffices("?limit=200"),
      listDivisions("?limit=500"),
      listStaff("?limit=500"),
      listJobStatuses("?limit=500"),
      listJobTitles("?limit=500"),
    ]);

    member.value = staffRes.data;
    photoUrl.value = staffRes.data.photoUrl;
    offices.value = officeRes.data;
    divisions.value = divRes.data;
    allStaff.value = allStaffRes.data;
    jobStatuses.value = jsRes.data;
    jobTitles.value = jtRes.data;

    const m = staffRes.data;
    form.name = m.name;
    form.dob = m.dob;
    form.phone = m.phone;
    form.sex = m.sex;
    form.jobTitleId = m.jobTitleId;
    form.jobStatusId = m.jobStatusId;
    form.addressLine1 = m.addressLine1;
    form.addressLine2 = m.addressLine2;
    form.addressTownship = m.addressTownship;
    form.addressPostcode = m.addressPostcode;
    form.addressState = m.addressState;
    form.officeId = m.officeId;
    form.divisionId = m.divisionId;
    form.supervisorId = m.supervisorId;
    originalDivisionId.value = m.divisionId;
  } catch (e) {
    toast.error("Failed to load staff member", e instanceof Error ? e.message : "");
    router.replace("/admin/hr/staff");
  } finally {
    loading.value = false;
  }
}

function resolveUrl(url: string | null): string {
  if (!url) return "";
  if (url.startsWith("http")) return url;
  return `${API_BASE_URL}${url}`;
}

async function onPhotoUpload(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (!file) return;
  uploadingPhoto.value = true;
  try {
    const res = await uploadStaffPhoto(staffId.value, file);
    photoUrl.value = res.data.photoUrl;
    toast.success("Photo updated");
  } catch (e) {
    toast.error("Upload failed", e instanceof Error ? e.message : "");
  } finally {
    uploadingPhoto.value = false;
    (event.target as HTMLInputElement).value = "";
  }
}

async function onPhotoRemove() {
  uploadingPhoto.value = true;
  try {
    await removeStaffPhoto(staffId.value);
    photoUrl.value = null;
    toast.success("Photo removed");
  } catch (e) {
    toast.error("Remove failed", e instanceof Error ? e.message : "");
  } finally {
    uploadingPhoto.value = false;
  }
}

async function save() {
  saving.value = true;
  try {
    await updateStaff(staffId.value, form);
    toast.success("Staff profile saved");
    originalDivisionId.value = form.divisionId ?? null;
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
      <AppBreadcrumb :items="[
        { label: 'Human Resources' },
        { label: 'Employees', to: '/admin/hr/staff' },
        { label: member?.name ?? 'Edit Employee' }
      ]" />

      <!-- Header -->
      <div class="flex items-center gap-3">
        <router-link
          to="/admin/hr/staff"
          class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
        >
          <ArrowLeft class="h-4 w-4" />
        </router-link>
        <div>
          <h1 class="page-title">{{ member?.name ?? 'Edit Staff' }}</h1>
          <p v-if="member" class="text-sm text-slate-400">{{ member.email }} · {{ member.role }}</p>
        </div>
      </div>

      <div v-if="loading" class="rounded-lg border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-400 shadow-sm">Loading…</div>

      <div v-else class="grid gap-4 lg:grid-cols-[1fr_320px]">
        <!-- ═══ LEFT COLUMN ═══ -->
        <div class="space-y-4">
          <!-- Personal Information -->
          <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
              <User class="h-4 w-4 text-violet-600" />
              <h2 class="text-sm font-semibold text-slate-900">Personal Information</h2>
            </div>
            <div class="space-y-3 p-4">
              <div class="grid gap-3 sm:grid-cols-2">
                <div class="space-y-1.5">
                  <label class="text-sm font-medium text-slate-700">Full Name</label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                  />
                </div>
                <div class="space-y-1.5">
                  <label class="text-sm font-medium text-slate-700">Email</label>
                  <input
                    :value="member?.email"
                    type="email"
                    disabled
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-400 shadow-sm"
                  />
                </div>
                <div class="space-y-1.5">
                  <label class="text-sm font-medium text-slate-700">Date of Birth</label>
                  <input
                    v-model="form.dob"
                    type="date"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                  />
                </div>
                <div class="space-y-1.5">
                  <label class="text-sm font-medium text-slate-700">Phone</label>
                  <input
                    v-model="form.phone"
                    type="tel"
                    placeholder="+60 12 345 6789"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                  />
                </div>
              </div>
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Sex</label>
                <div class="flex gap-4">
                  <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="radio" v-model="form.sex" value="male" class="accent-violet-600" />
                    Male
                  </label>
                  <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="radio" v-model="form.sex" value="female" class="accent-violet-600" />
                    Female
                  </label>
                </div>
              </div>
              <!-- Address -->
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Home Address</label>
                <div class="space-y-2">
                  <input
                    v-model="form.addressLine1"
                    type="text"
                    placeholder="House / Unit No."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                  />
                  <input
                    v-model="form.addressLine2"
                    type="text"
                    placeholder="Road (Jalan / Lorong…)"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                  />
                  <input
                    v-model="form.addressTownship"
                    type="text"
                    placeholder="Township / Area"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                  />
                  <div class="grid grid-cols-2 gap-2">
                    <input
                      v-model="form.addressPostcode"
                      type="text"
                      placeholder="Postcode"
                      maxlength="5"
                      class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    />
                    <AppSelect
                      v-model="form.addressState"
                      :options="MY_STATE_OPTIONS"
                      placeholder="State"
                    />
                  </div>
                </div>
              </div>
            </div>
          </article>

          <!-- Employment -->
          <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
              <Briefcase class="h-4 w-4 text-amber-600" />
              <h2 class="text-sm font-semibold text-slate-900">Employment</h2>
            </div>
            <div class="grid gap-3 p-4 sm:grid-cols-2">
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Job Title</label>
                <AppSelect
                  v-model="form.jobTitleId"
                  :options="jobTitleOptions"
                  placeholder="— Not specified —"
                />
              </div>
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Employment Status</label>
                <AppSelect
                  v-model="form.jobStatusId"
                  :options="jobStatusOptions"
                  placeholder="— Not specified —"
                />
              </div>
            </div>
          </article>

          <!-- Save -->
          <div class="flex items-center gap-3">
            <button
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-6 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-50"
              :disabled="saving"
              @click="save"
            >
              <Save class="h-4 w-4" />
              {{ saving ? 'Saving…' : 'Save Changes' }}
            </button>
            <router-link
              to="/admin/hr/staff"
              class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
            >
              Cancel
            </router-link>
          </div>
        </div>

        <!-- ═══ RIGHT COLUMN ═══ -->
        <div class="space-y-4">
          <!-- Photo -->
          <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
              <Camera class="h-4 w-4 text-violet-600" />
              <h2 class="text-sm font-semibold text-slate-900">Profile Photo</h2>
            </div>
            <div class="flex flex-col items-center gap-3 p-4">
              <!-- Avatar preview -->
              <div class="relative">
                <img
                  v-if="photoUrl"
                  :src="resolveUrl(photoUrl)"
                  alt="Staff photo"
                  class="h-36 w-36 rounded-full border-2 border-slate-200 object-cover"
                />
                <div
                  v-else
                  class="flex h-36 w-36 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-indigo-500 text-3xl font-semibold text-white"
                >
                  {{ (member?.name ?? '?').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) }}
                </div>
              </div>
              <!-- Actions -->
              <div class="flex gap-2">
                <label
                  class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50"
                  :class="uploadingPhoto ? 'pointer-events-none opacity-50' : ''"
                >
                  <Upload class="h-3.5 w-3.5" />
                  {{ uploadingPhoto ? 'Uploading…' : 'Upload' }}
                  <input type="file" accept="image/*" class="hidden" :disabled="uploadingPhoto" @change="onPhotoUpload" />
                </label>
                <button
                  v-if="photoUrl"
                  class="flex items-center gap-1.5 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 transition-colors hover:bg-rose-50"
                  :disabled="uploadingPhoto"
                  @click="onPhotoRemove"
                >
                  <Trash2 class="h-3.5 w-3.5" />
                  Remove
                </button>
              </div>
              <p class="text-xs text-slate-400">JPG, PNG or GIF · Max 2MB</p>
            </div>
          </article>

          <!-- Organisation -->
          <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
              <Building2 class="h-4 w-4 text-indigo-600" />
              <h2 class="text-sm font-semibold text-slate-900">Organisation</h2>
            </div>
            <div class="space-y-3 p-4">
              <!-- Office Location -->
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Office Location</label>
                <AppSelect
                  v-model="form.officeId"
                  :options="officeOptions"
                  placeholder="— No office —"
                />
              </div>

              <!-- Division -->
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Division</label>
                <AppSelect
                  v-model="form.divisionId"
                  :options="divisionOptions"
                  placeholder="— No division —"
                />
                <div v-if="divisionChanged" class="flex items-start gap-1.5 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                  <ArrowRightLeft class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                  Transfer will take effect on {{ transferEffectiveDate() }}
                </div>
              </div>

              <!-- Supervisor -->
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Supervisor</label>
                <AppSelect
                  v-model="form.supervisorId"
                  :options="supervisorOptions"
                  placeholder="— No supervisor —"
                />
              </div>

              <!-- Pending transfer notice -->
              <div v-if="member?.pendingTransfer && !divisionChanged" class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                <p class="font-medium">Transfer pending</p>
                <p>→ {{ member.pendingTransfer.toDivisionName }} on {{ member.pendingTransfer.effectiveDate }}</p>
              </div>
            </div>
          </article>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
