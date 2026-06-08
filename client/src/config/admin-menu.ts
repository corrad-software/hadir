import type { Component } from "vue";
import {
  BookOpen,
  Building2,
  Cable,
  ClipboardCheck,
  ClipboardList,
  Clock,
  Database,
  Eye,
  Gauge,
  LayoutGrid,
  ListChecks,
  Map,
  Settings,
  Shield,
  SlidersHorizontal,
  Users,
} from "lucide-vue-next";

export type MenuNode = {
  id: string;
  label: string;
  to: string;
  children?: MenuNode[];
};

export type MenuItemDef = MenuNode & {
  icon: Component;
  roles?: string[];
};

export type MenuGroupDef = {
  id: string;
  label: string;
  items: MenuItemDef[];
  roles?: string[];
};

export type AdminMenuPrefs = {
  groupOrder: string[];
  itemOrder: Record<string, string[]>;
  childOrder: Record<string, string[]>;
  grandchildOrder: Record<string, string[]>;
  hidden: string[];
  hiddenChildren: string[];
  hiddenGrandchildren: string[];
  hiddenGroups: string[];
};

export const DEFAULT_MENU: MenuGroupDef[] = [
  {
    id: "dashboard",
    label: "",
    items: [
      { id: "main-dashboard", label: "Dashboard", to: "/admin", icon: Gauge },
    ],
  },
  {
    id: "attendance",
    label: "Attendance",
    items: [
      {
        id: "attendance-my",
        label: "My Attendance",
        icon: Clock,
        to: "/admin/attendance/checkin",
        children: [
          { id: "attendance-checkin", label: "Check In / Out", to: "/admin/attendance/checkin" },
          { id: "attendance-my-history", label: "My History", to: "/admin/attendance/my-history" },
        ],
      },
      {
        id: "attendance-approvals",
        label: "Approvals",
        icon: ClipboardList,
        to: "/admin/attendance/approvals",
        roles: ["admin", "hr_admin", "supervisor"],
      },
      {
        id: "attendance-corrections",
        label: "Corrections",
        icon: ClipboardCheck,
        to: "/admin/attendance/corrections",
      },
      {
        id: "attendance-team-map",
        label: "Team Map",
        icon: Map,
        to: "/admin/attendance/team-map",
      },
      {
        id: "attendance-management",
        label: "Management",
        icon: ListChecks,
        to: "/admin/attendance/records",
        roles: ["admin", "hr_admin"],
        children: [
          { id: "attendance-records", label: "All Records", to: "/admin/attendance/records" },
          { id: "attendance-report", label: "Reports", to: "/admin/attendance/report" },
        ],
      },
    ],
  },
  {
    id: "human-resources",
    label: "Human Resources",
    items: [
      {
        id: "hr-staff",
        label: "Employees",
        icon: Users,
        to: "/admin/hr/staff",
        roles: ["admin", "hr_admin"],
      },
      {
        id: "hr-divisions",
        label: "Divisions",
        icon: Building2,
        to: "/admin/hr/divisions",
        roles: ["admin", "hr_admin"],
      },
      {
        id: "hr-configuration",
        label: "Configuration",
        icon: SlidersHorizontal,
        to: "/admin/hr/configuration/job-statuses",
        roles: ["admin", "hr_admin"],
        children: [
          { id: "hr-job-statuses", label: "Job Statuses", to: "/admin/hr/configuration/job-statuses" },
          { id: "hr-job-titles", label: "Job Titles", to: "/admin/hr/configuration/job-titles" },
          { id: "hr-offices", label: "Office Locations", to: "/admin/attendance/offices" },
          { id: "hr-work-policies", label: "Work Policies", to: "/admin/attendance/policies" },
        ],
      },
    ],
  },
  {
    id: "administration",
    label: "Administration",
    items: [
      {
        id: "identity-access",
        label: "Users & Roles",
        to: "/admin/platform/identity/users",
        icon: Shield,
        roles: ["admin"],
        children: [
          { id: "platform-users", label: "Users", to: "/admin/platform/identity/users" },
          { id: "platform-rbac", label: "Roles", to: "/admin/platform/identity/roles" },
        ],
      },
      {
        id: "observability",
        label: "Audit Trail",
        to: "/admin/platform/observability/audit-trail",
        icon: Eye,
        roles: ["admin"],
      },
      {
        id: "queue",
        label: "Queue Monitor",
        to: "/admin/platform/queue",
        icon: ListChecks,
        roles: ["admin"],
      },
      {
        id: "settings",
        label: "Settings",
        to: "/admin/settings",
        icon: Settings,
        roles: ["admin"],
        children: [
          { id: "settings-general", label: "General", to: "/admin/settings" },
          { id: "settings-system", label: "System", to: "/admin/settings/system" },
        ],
      },
    ],
  },
  {
    id: "development",
    label: "Development",
    roles: ["admin", "hr_admin"],
    items: [
      { id: "developers-guide", label: "Developers Guide", to: "/admin/development/developers-guide", icon: BookOpen },
      { id: "database-schema", label: "Database Schema", to: "/admin/development/database-schema", icon: Database },
      { id: "api-explorer", label: "API Explorer", to: "/admin/development/api-explorer", icon: Cable },
      {
        id: "kitchen-sink",
        label: "Kitchen Sink",
        to: "/admin/kitchen-sink",
        icon: LayoutGrid,
        children: [
          { id: "kitchen-components", label: "Components", to: "/admin/kitchen-sink" },
          { id: "kitchen-forms", label: "Forms", to: "/admin/kitchen-sink/forms" },
          { id: "kitchen-charts", label: "Charts", to: "/admin/kitchen-sink/charts" },
        ],
      },
    ],
  },
];
