<script setup lang="ts">
import { onMounted, ref } from "vue";
import {
  Globe,
  Image,
  Search,
  Save,
  CheckCircle2,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import { getSettings, updateSettings } from "@/api/cms";
import { useToast } from "@/composables/useToast";
import { useSiteStore } from "@/stores/site";
import { useRoute } from "vue-router";
import type { SettingsPayload } from "@/types";

const site = useSiteStore();
const route = useRoute();
const toast = useToast();

const form = ref<SettingsPayload>({
  siteTitle: "",
  tagline: "",
  webfrontTitle: "",
  webfrontTagline: "",
  titleFormat: "%page% | %site%",
  metaDescription: "",
  siteIconUrl: "",
  webfrontLogoUrl: "",
  sidebarLogoUrl: "",
  faviconUrl: "",
  language: "en",
  timezone: "UTC",
  footerText: "",
  frontPageId: null,
});

const saved = ref(false);
const saving = ref(false);
const error = ref("");

async function load() {
  try {
    const res = await getSettings();
    form.value = res.data;
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : "Failed to load settings";
  }
}

async function save() {
  saving.value = true;
  error.value = "";
  try {
    await updateSettings(form.value);
    site.applyFrom(form.value);
    site.setDocumentTitle((route.meta.title as string) || "Settings");
    saved.value = true;
    toast.success("Settings saved");
    setTimeout(() => { saved.value = false; }, 2000);
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : "Failed to save settings";
    toast.error("Save failed", error.value);
  } finally {
    saving.value = false;
  }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <div class="flex items-center justify-between">
        <h1 class="page-title">Settings</h1>
      </div>

      <div class="space-y-4">
        <!-- General -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
            <Globe class="h-4 w-4 text-violet-600" />
            <h2 class="text-sm font-semibold text-slate-900">General</h2>
          </div>
          <div class="p-4">
            <div class="grid gap-3 md:grid-cols-3">
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Site Title</label>
                <input v-model="form.siteTitle" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
              </div>
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Tagline</label>
                <input v-model="form.tagline" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
              </div>
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Language</label>
                <input v-model="form.language" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
              </div>
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Timezone</label>
                <input v-model="form.timezone" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
              </div>
            </div>
          </div>
        </article>

        <!-- SEO -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
            <Search class="h-4 w-4 text-blue-600" />
            <h2 class="text-sm font-semibold text-slate-900">SEO</h2>
          </div>
          <div class="p-4">
            <div class="grid gap-3 md:grid-cols-2">
              <div class="space-y-1.5 md:col-span-2">
                <label class="text-sm font-medium text-slate-700">Title Format</label>
                <input v-model="form.titleFormat" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                <p class="text-xs text-slate-400">Use <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">%page%</code> and <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">%site%</code> as placeholders.</p>
              </div>
              <div class="space-y-1.5 md:col-span-2">
                <label class="text-sm font-medium text-slate-700">Meta Description</label>
                <textarea v-model="form.metaDescription" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
              </div>
            </div>
          </div>
        </article>

        <!-- Branding -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
            <Image class="h-4 w-4 text-amber-600" />
            <h2 class="text-sm font-semibold text-slate-900">Branding</h2>
          </div>
          <div class="p-4">
            <div class="grid gap-3 md:grid-cols-2">
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Site Icon URL</label>
                <input v-model="form.siteIconUrl" placeholder="https://..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
              </div>
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Favicon URL</label>
                <input v-model="form.faviconUrl" placeholder="https://..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
              </div>
              <div class="space-y-1.5">
                <label class="text-sm font-medium text-slate-700">Sidebar Logo URL</label>
                <input v-model="form.sidebarLogoUrl" placeholder="https://..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
              </div>
            </div>
          </div>
        </article>

        <!-- Actions -->
        <div class="space-y-3">
          <div class="flex items-center gap-3">
            <button
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-50"
              :disabled="saving"
              @click="save"
            >
              <Save class="h-4 w-4" />
              {{ saving ? 'Saving...' : 'Save Settings' }}
            </button>
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="translate-y-1 opacity-0"
              enter-to-class="translate-y-0 opacity-100"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <span v-if="saved" class="flex items-center gap-1.5 text-sm font-medium text-emerald-600">
                <CheckCircle2 class="h-4 w-4" />
                Saved
              </span>
            </Transition>
          </div>
          <p v-if="error" class="text-sm text-rose-600">{{ error }}</p>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
