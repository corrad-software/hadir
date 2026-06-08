import { createRouter, createWebHistory } from "vue-router";
import type { RouteLocationGeneric, RouteRecordRaw } from "vue-router";

import MainDashboardView from "@/views/MainDashboardView.vue";
import KitchenChartsView from "@/views/KitchenChartsView.vue";
import KitchenFormsView from "@/views/KitchenFormsView.vue";
import LoginView from "@/views/LoginView.vue";
import KitchenSinkView from "@/views/KitchenSinkView.vue";
import DatabaseSchemaView from "@/views/DatabaseSchemaView.vue";
import DevelopersGuideView from "@/views/DevelopersGuideView.vue";
import ApiManagementView from "@/views/ApiManagementView.vue";
import MenusView from "@/views/MenusView.vue";
import AuditLogsView from "@/views/AuditLogsView.vue";
import QueueMonitorView from "@/views/QueueMonitorView.vue";
import ComingSoonView from "@/views/ComingSoonView.vue";
import RolesView from "@/views/RolesView.vue";
import SettingsView from "@/views/SettingsView.vue";
import SystemInfoView from "@/views/SystemInfoView.vue";
import UsersView from "@/views/UsersView.vue";
import UserEditView from "@/views/UserEditView.vue";
import AttendanceCheckInView from "@/views/AttendanceCheckInView.vue";
import AttendanceMyHistoryView from "@/views/AttendanceMyHistoryView.vue";
import AttendancePolicyView from "@/views/AttendancePolicyView.vue";
import AttendanceAllRecordsView from "@/views/AttendanceAllRecordsView.vue";
import AttendanceReportView from "@/views/AttendanceReportView.vue";
import AttendanceApprovalView from "@/views/AttendanceApprovalView.vue";
import AttendanceCorrectionsView from "@/views/AttendanceCorrectionsView.vue";
import TeamAttendanceMapView from "@/views/TeamAttendanceMapView.vue";
import DivisionsView from "@/views/DivisionsView.vue";
import JobStatusesView from "@/views/JobStatusesView.vue";
import JobTitlesView from "@/views/JobTitlesView.vue";
import StaffView from "@/views/StaffView.vue";
import StaffEditorView from "@/views/StaffEditorView.vue";
import OfficesView from "@/views/OfficesView.vue";
import OfficeEditorView from "@/views/OfficeEditorView.vue";
import AttendancePolicyEditorView from "@/views/AttendancePolicyEditorView.vue";
import { useAuthStore } from "@/stores/auth";
import { useSiteStore } from "@/stores/site";

// Backward-compat redirects: old /admin/settings/* → new /admin/platform/* paths
const settingsRedirects: RouteRecordRaw[] = [
  { path: "/admin/settings/users", redirect: "/admin/platform/identity/users" },
  { path: "/admin/settings/users/new", redirect: "/admin/platform/identity/users/new" },
  { path: "/admin/settings/users/:id", redirect: (to: RouteLocationGeneric) => `/admin/platform/identity/users/${String(to.params.id ?? "")}` },
  { path: "/admin/settings/roles", redirect: "/admin/platform/identity/roles" },
  { path: "/admin/settings/audit-logs", redirect: "/admin/platform/observability/audit-trail" },
  { path: "/admin/settings/queue-monitor", redirect: "/admin/platform/queue" },
];

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: "/", redirect: "/admin/login" },
    { path: "/admin/login", name: "login", component: LoginView, meta: { guestOnly: true, title: "Login" } },
    { path: "/admin", name: "main-dashboard", component: MainDashboardView, meta: { requiresAuth: true, title: "Main Dashboard" } },
    { path: "/admin/kitchen-sink", name: "kitchen-sink", component: KitchenSinkView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Kitchen Sink" } },
    { path: "/admin/kitchen-sink/forms", name: "kitchen-forms", component: KitchenFormsView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Forms" } },
    { path: "/admin/kitchen-sink/charts", name: "kitchen-charts", component: KitchenChartsView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Charts" } },
    { path: "/admin/development/developers-guide", name: "developers-guide", component: DevelopersGuideView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Developers Guide" } },
    { path: "/admin/development/database-schema", name: "database-schema", component: DatabaseSchemaView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Database Schema" } },
    { path: "/admin/development/api-explorer", name: "api-explorer", component: ApiManagementView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "API Explorer" } },
    { path: "/admin/development/api-management", redirect: "/admin/development/api-explorer" },
    {
      path: "/admin/profile",
      name: "profile",
      meta: { requiresAuth: true },
      beforeEnter: async () => {
        const auth = useAuthStore();
        await auth.initialize();
        if (auth.user?.id) return `/admin/platform/identity/users/${auth.user.id}`;
        return { name: "login" };
      },
      component: { template: "" },
    },

    // ── Attendance ──
    { path: "/admin/attendance/checkin", name: "attendance-checkin", component: AttendanceCheckInView, meta: { requiresAuth: true, title: "Check In / Out" } },
    { path: "/admin/attendance/my-history", name: "attendance-my-history", component: AttendanceMyHistoryView, meta: { requiresAuth: true, title: "My Attendance" } },
    { path: "/admin/attendance/policies", name: "attendance-policies", component: AttendancePolicyView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Work Policies" } },
    { path: "/admin/attendance/policies/new", name: "attendance-policy-create", component: AttendancePolicyEditorView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "New Policy" } },
    { path: "/admin/attendance/policies/:id/edit", name: "attendance-policy-edit", component: AttendancePolicyEditorView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Edit Policy" } },
    { path: "/admin/attendance/records", name: "attendance-records", component: AttendanceAllRecordsView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "All Records" } },
    { path: "/admin/attendance/report", name: "attendance-report", component: AttendanceReportView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Attendance Report" } },
    { path: "/admin/attendance/approvals", name: "attendance-approvals", component: AttendanceApprovalView, meta: { requiresAuth: true, roles: ["admin", "hr_admin", "supervisor"], title: "Attendance Approvals" } },
    { path: "/admin/attendance/corrections", name: "attendance-corrections", component: AttendanceCorrectionsView, meta: { requiresAuth: true, title: "Attendance Corrections" } },
    { path: "/admin/attendance/team-map", name: "attendance-team-map", component: TeamAttendanceMapView, meta: { requiresAuth: true, title: "Team Attendance Map" } },
    { path: "/admin/attendance/offices", name: "attendance-offices", component: OfficesView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Office Locations" } },
    { path: "/admin/attendance/offices/new", name: "attendance-office-create", component: OfficeEditorView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "New Office Location" } },
    { path: "/admin/attendance/offices/:id/edit", name: "attendance-office-edit", component: OfficeEditorView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Edit Office Location" } },

    // ── HR Management ──
    { path: "/admin/hr/divisions", name: "hr-divisions", component: DivisionsView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Divisions" } },
    { path: "/admin/hr/staff", name: "hr-staff", component: StaffView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Employees" } },
    { path: "/admin/hr/staff/:id/edit", name: "hr-staff-edit", component: StaffEditorView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Edit Employee" } },
    { path: "/admin/hr/configuration/job-statuses", name: "hr-job-statuses", component: JobStatusesView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Job Statuses" } },
    { path: "/admin/hr/configuration/job-titles", name: "hr-job-titles", component: JobTitlesView, meta: { requiresAuth: true, roles: ["admin", "hr_admin"], title: "Job Titles" } },

    // ── Administration ──
    { path: "/admin/settings", name: "settings", component: SettingsView, meta: { requiresAuth: true, roles: ["admin"], title: "Settings" } },
    { path: "/admin/settings/system", name: "settings-system", component: SystemInfoView, meta: { requiresAuth: true, roles: ["admin"], title: "System Info" } },
    { path: "/admin/menus", name: "menus", component: MenusView, meta: { requiresAuth: true, roles: ["admin"], title: "Menus" } },

    // ── Core Platform: Identity & Access ──
    { path: "/admin/platform/identity", redirect: "/admin/platform/identity/users" },
    { path: "/admin/platform/identity/users", name: "platform-users", component: UsersView, meta: { requiresAuth: true, roles: ["admin"], title: "Users" } },
    { path: "/admin/platform/identity/users/new", name: "platform-user-create", component: UserEditView, meta: { requiresAuth: true, roles: ["admin"], title: "New User" } },
    { path: "/admin/platform/identity/users/:id", name: "platform-user-edit", component: UserEditView, meta: { requiresAuth: true, roles: ["admin"], title: "Edit User" } },
    { path: "/admin/platform/identity/roles", name: "platform-rbac", component: RolesView, meta: { requiresAuth: true, roles: ["admin"], title: "RBAC" } },
    { path: "/admin/platform/identity/tokens", name: "platform-tokens", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Token Management" } },

    // ── Core Platform: Observability ──
    { path: "/admin/platform/observability", redirect: "/admin/platform/observability/audit-trail" },
    { path: "/admin/platform/observability/audit-trail", name: "platform-audit-trail", component: AuditLogsView, meta: { requiresAuth: true, roles: ["admin"], title: "Audit Trail" } },
    { path: "/admin/platform/observability/activity-log", name: "platform-activity-log", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Activity Log" } },
    { path: "/admin/platform/observability/logging", name: "platform-logging", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Logging" } },
    { path: "/admin/platform/observability/errors", name: "platform-error-tracking", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Error Tracking" } },
    { path: "/admin/platform/observability/monitoring", name: "platform-monitoring", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Monitoring" } },

    // ── Core Platform: Queue ──
    { path: "/admin/platform/queue", name: "platform-queue", component: QueueMonitorView, meta: { requiresAuth: true, roles: ["admin"], title: "Queue" } },
    { path: "/admin/platform/queue/failed", name: "platform-queue-failed", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Failed Jobs" } },
    { path: "/admin/platform/queue/scheduled", name: "platform-queue-scheduled", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Scheduled Jobs" } },

    // ── Core Platform: Messaging ──
    { path: "/admin/platform/messaging", redirect: "/admin/platform/messaging/event-bus" },
    { path: "/admin/platform/messaging/event-bus", name: "platform-event-bus", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Event Bus" } },
    { path: "/admin/platform/messaging/notifications", name: "platform-notifications", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Notifications" } },

    // ── Backward-compat redirects ──
    { path: "/admin/platform/governance", redirect: "/admin/platform/observability/audit-trail" },
    { path: "/admin/platform/governance/audit-trail", redirect: "/admin/platform/observability/audit-trail" },
    { path: "/admin/platform/governance/activity-log", redirect: "/admin/platform/observability/activity-log" },
    { path: "/admin/platform/communication", redirect: "/admin/platform/messaging/notifications" },
    { path: "/admin/platform/communication/notifications", redirect: "/admin/platform/messaging/notifications" },
    { path: "/admin/platform/messaging/queue", redirect: "/admin/platform/queue" },
    { path: "/admin/platform/messaging/queue/failed", redirect: "/admin/platform/queue/failed" },
    { path: "/admin/platform/messaging/queue/scheduled", redirect: "/admin/platform/queue/scheduled" },

    // ── Core Platform: System Management ──
    { path: "/admin/platform/system", redirect: "/admin/platform/system/configuration" },
    { path: "/admin/platform/system/configuration", name: "platform-config", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Configuration" } },
    { path: "/admin/platform/system/feature-flags", name: "platform-feature-flags", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Feature Flags" } },
    { path: "/admin/platform/system/scheduler", name: "platform-scheduler", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Scheduler" } },

    // ── Core Platform: Storage ──
    { path: "/admin/platform/storage", redirect: "/admin/platform/storage/media" },
    { path: "/admin/platform/storage/media", name: "platform-file-media", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "File / Media Management" } },

    // ── Core Platform: API Gateway ──
    { path: "/admin/platform/gateway", redirect: "/admin/platform/gateway/routes" },
    { path: "/admin/platform/gateway/routes", name: "platform-gateway-routes", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Routes" } },
    { path: "/admin/platform/gateway/upstreams", name: "platform-gateway-upstreams", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Upstreams" } },
    { path: "/admin/platform/gateway/consumers", name: "platform-gateway-consumers", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Consumers" } },
    { path: "/admin/platform/gateway/plugins", name: "platform-gateway-plugins", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Plugins" } },
    { path: "/admin/platform/gateway/ssl", name: "platform-gateway-ssl", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "SSL Certificates" } },
    { path: "/admin/platform/gateway/webhooks", name: "platform-webhooks", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Webhooks" } },

    // ── Backward-compat redirects from old integration paths ──
    { path: "/admin/platform/integration", redirect: "/admin/platform/gateway/routes" },
    { path: "/admin/platform/integration/api", redirect: "/admin/platform/gateway/routes" },
    { path: "/admin/platform/integration/webhooks", redirect: "/admin/platform/gateway/webhooks" },

    // ── Core Platform: AI Integration ──
    { path: "/admin/platform/ai", redirect: "/admin/platform/ai/providers" },
    { path: "/admin/platform/ai/providers", name: "platform-ai-providers", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "AI Providers" } },
    { path: "/admin/platform/ai/models", name: "platform-ai-models", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "AI Models" } },
    { path: "/admin/platform/ai/prompts", name: "platform-ai-prompts", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "Prompt Templates" } },
    { path: "/admin/platform/ai/usage", name: "platform-ai-usage", component: ComingSoonView, meta: { requiresAuth: true, roles: ["admin"], title: "AI Usage & Billing" } },

    ...settingsRedirects,
  ],
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  await auth.initialize();

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: "login" };
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: "main-dashboard" };
  }

  if (to.meta.roles) {
    const allowed = to.meta.roles as string[];
    const role = (auth.user?.role ?? "").toLowerCase();
    const effectiveRoles = [role];
    if (auth.user?.hasSupervisees) effectiveRoles.push("supervisor");

    // Allow admin to edit their own profile even on admin-only routes
    if (to.name === "platform-user-edit" && String(to.params.id) === String(auth.user?.id)) {
      return true;
    }

    if (!effectiveRoles.some((r) => allowed.includes(r))) {
      return { name: "main-dashboard" };
    }
  }

  return true;
});

router.afterEach((to) => {
  const site = useSiteStore();
  const pageTitle = (to.meta.title as string) || "Admin";
  site.setDocumentTitle(pageTitle);
});

export default router;
