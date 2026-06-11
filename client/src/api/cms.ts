import { apiRequest, ensureCsrfCookie } from "./client";
import type {
  AppNotification,
  AttendanceCorrection,
  AttendanceCorrectionInput,
  AttendanceLog,
  AttendanceLogUpdateInput,
  AttendancePolicy,
  AttendancePolicyInput,
  AttendanceReportRow,
  AuditLog,
  CheckInInput,
  Division,
  DivisionInput,
  DivisionTransfer,
  JobStatus,
  JobStatusInput,
  JobTitle,
  JobTitleInput,
  Office,
  OfficeInput,
  Role,
  RoleInput,
  SettingsPayload,
  StaffMember,
  StaffUpdateInput,
  TeamMemberAttendance,
  UserDetail,
  UserInput,
} from "@/types";
import type { AdminMenuPrefs } from "@/config/admin-menu";

export async function getSettings() {
  return apiRequest<{ data: SettingsPayload }>("/api/settings");
}

export async function updateSettings(payload: SettingsPayload) {
  return apiRequest<{ data: SettingsPayload }>("/api/settings", {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export async function getAdminMenuPrefs() {
  return apiRequest<{ data: AdminMenuPrefs | null }>("/api/settings/admin-menu-prefs");
}

export async function saveAdminMenuPrefs(prefs: AdminMenuPrefs) {
  return apiRequest<{ data: AdminMenuPrefs }>("/api/settings/admin-menu-prefs", {
    method: "PUT",
    body: JSON.stringify(prefs),
  });
}

// Users
export async function listUsers(params = "") {
  return apiRequest<{ data: UserDetail[]; meta: Record<string, unknown> }>(`/api/users${params}`);
}

export async function getUser(id: number) {
  return apiRequest<{ data: UserDetail }>(`/api/users/${id}`);
}

export async function createUser(input: UserInput) {
  await ensureCsrfCookie();
  return apiRequest<{ data: UserDetail }>("/api/users", { method: "POST", body: JSON.stringify(input) });
}

export async function updateUser(id: number, input: UserInput) {
  await ensureCsrfCookie();
  return apiRequest<{ data: UserDetail }>(`/api/users/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteUser(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/users/${id}`, { method: "DELETE" });
}

export async function syncUsersFromSso() {
  return apiRequest<{ data: { created: number; updated: number; total: number } }>("/api/users/sync-sso", { method: "POST" });
}

export async function resetUserPassword(id: number, password: string, passwordConfirmation: string) {
  return apiRequest<{ data: { success: boolean } }>(`/api/users/${id}/reset-password`, {
    method: "POST",
    body: JSON.stringify({ password, password_confirmation: passwordConfirmation }),
  });
}

// Roles
export async function listRoles() {
  return apiRequest<{ data: Role[] }>("/api/roles");
}

export async function getRole(id: number) {
  return apiRequest<{ data: Role }>(`/api/roles/${id}`);
}

export async function createRole(input: RoleInput) {
  return apiRequest<{ data: Role }>("/api/roles", { method: "POST", body: JSON.stringify(input) });
}

export async function updateRole(id: number, input: RoleInput) {
  return apiRequest<{ data: Role }>(`/api/roles/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteRole(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/roles/${id}`, { method: "DELETE" });
}

// Audit Logs
export async function listAuditLogs(params = "") {
  return apiRequest<{ data: AuditLog[]; meta: Record<string, unknown> }>(`/api/audit-logs${params}`);
}

// Developers Guide
export async function getDevelopersGuide() {
  return apiRequest<{ data: { content: string; syncFiles: { filename: string; path?: string; exists: boolean; inSync: boolean; readOnly?: boolean; role?: "canonical" | "mirror" }[] } }>("/api/developers-guide");
}

export async function updateDevelopersGuide(content: string) {
  return apiRequest<{ data: { success: boolean; syncFiles: { filename: string; path?: string; exists: boolean; inSync: boolean; readOnly?: boolean; role?: "canonical" | "mirror" }[] } }>("/api/developers-guide", {
    method: "PUT",
    body: JSON.stringify({ content }),
  });
}

// Attendance Policies
export async function getAttendancePolicy(id: number) {
  return apiRequest<{ data: AttendancePolicy }>(`/api/attendance-policies/${id}`);
}

export async function listAttendancePolicies() {
  return apiRequest<{ data: AttendancePolicy[] }>("/api/attendance-policies");
}

export async function createAttendancePolicy(payload: AttendancePolicyInput) {
  return apiRequest<{ data: AttendancePolicy }>("/api/attendance-policies", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export async function updateAttendancePolicy(id: number, payload: Partial<AttendancePolicyInput>) {
  return apiRequest<{ data: AttendancePolicy }>(`/api/attendance-policies/${id}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export async function deleteAttendancePolicy(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/attendance-policies/${id}`, { method: "DELETE" });
}

export async function activateAttendancePolicy(id: number) {
  return apiRequest<{ data: AttendancePolicy }>(`/api/attendance-policies/${id}/activate`, { method: "POST" });
}

// Offices
export async function getOffice(id: number) {
  return apiRequest<{ data: Office }>(`/api/offices/${id}`);
}

export async function listOffices(params = "") {
  return apiRequest<{ data: Office[]; meta: Record<string, unknown> }>(`/api/offices${params}`);
}

export async function createOffice(input: OfficeInput) {
  return apiRequest<{ data: Office }>("/api/offices", { method: "POST", body: JSON.stringify(input) });
}

export async function updateOffice(id: number, input: Partial<OfficeInput>) {
  return apiRequest<{ data: Office }>(`/api/offices/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteOffice(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/offices/${id}`, { method: "DELETE" });
}

// Attendance Logs
export async function getTodayAttendance() {
  return apiRequest<{ data: AttendanceLog }>("/api/attendance/today");
}

export async function getAttendanceTodayStats() {
  return apiRequest<{ data: { checkedIn: number; late: number; outsideRadius: number } }>("/api/attendance/today/stats");
}

export async function getTeamToday() {
  return apiRequest<{ data: TeamMemberAttendance[] }>("/api/attendance/team/today");
}

export async function checkIn(payload: CheckInInput) {
  return apiRequest<{ data: AttendanceLog }>("/api/attendance/checkin", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export async function checkOut(payload: CheckInInput) {
  return apiRequest<{ data: AttendanceLog }>("/api/attendance/checkout", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export async function listMyAttendance(params = "") {
  return apiRequest<{ data: AttendanceLog[]; meta: Record<string, unknown> }>(`/api/attendance/my${params}`);
}

export async function listAllAttendance(params = "") {
  return apiRequest<{ data: AttendanceLog[]; meta: Record<string, unknown> }>(`/api/attendance${params}`);
}

export async function getAttendanceLog(id: number) {
  return apiRequest<{ data: AttendanceLog }>(`/api/attendance/${id}`);
}

export async function updateAttendanceLog(id: number, payload: AttendanceLogUpdateInput) {
  return apiRequest<{ data: AttendanceLog }>(`/api/attendance/${id}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export async function getAttendanceReport(params = "") {
  return apiRequest<{ data: AttendanceReportRow[] }>(`/api/attendance/report${params}`);
}

export async function approveAttendance(id: number) {
  return apiRequest<{ data: AttendanceLog }>(`/api/attendance/${id}/approve`, { method: "POST" });
}

export async function rejectAttendance(id: number, rejectionReason: string) {
  return apiRequest<{ data: AttendanceLog }>(`/api/attendance/${id}/reject`, {
    method: "POST",
    body: JSON.stringify({ rejection_reason: rejectionReason }),
  });
}

// Job Statuses
export async function listJobStatuses(params = "") {
  return apiRequest<{ data: JobStatus[]; meta: Record<string, unknown> }>(`/api/job-statuses${params}`);
}
export async function createJobStatus(input: JobStatusInput) {
  return apiRequest<{ data: JobStatus }>("/api/job-statuses", { method: "POST", body: JSON.stringify(input) });
}
export async function updateJobStatus(id: number, input: JobStatusInput) {
  return apiRequest<{ data: JobStatus }>(`/api/job-statuses/${id}`, { method: "PUT", body: JSON.stringify(input) });
}
export async function deleteJobStatus(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/job-statuses/${id}`, { method: "DELETE" });
}

// Job Titles
export async function listJobTitles(params = "") {
  return apiRequest<{ data: JobTitle[]; meta: Record<string, unknown> }>(`/api/job-titles${params}`);
}
export async function createJobTitle(input: JobTitleInput) {
  return apiRequest<{ data: JobTitle }>("/api/job-titles", { method: "POST", body: JSON.stringify(input) });
}
export async function updateJobTitle(id: number, input: JobTitleInput) {
  return apiRequest<{ data: JobTitle }>(`/api/job-titles/${id}`, { method: "PUT", body: JSON.stringify(input) });
}
export async function deleteJobTitle(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/job-titles/${id}`, { method: "DELETE" });
}

// Divisions
export async function listDivisions(params = "") {
  return apiRequest<{ data: Division[]; meta: Record<string, unknown> }>(`/api/divisions${params}`);
}

export async function getDivision(id: number) {
  return apiRequest<{ data: Division }>(`/api/divisions/${id}`);
}

export async function createDivision(input: DivisionInput) {
  return apiRequest<{ data: Division }>("/api/divisions", { method: "POST", body: JSON.stringify(input) });
}

export async function updateDivision(id: number, input: Partial<DivisionInput>) {
  return apiRequest<{ data: Division }>(`/api/divisions/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteDivision(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/divisions/${id}`, { method: "DELETE" });
}

// Staff
export async function uploadStaffPhoto(id: number, file: File) {
  const body = new FormData();
  body.append("file", file);
  return apiRequest<{ data: { photoUrl: string } }>(`/api/staff/${id}/photo`, { method: "POST", body });
}

export async function removeStaffPhoto(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/staff/${id}/photo`, { method: "DELETE" });
}

export async function getStaffStats() {
  return apiRequest<{ data: { total: number; withDivision: number; withSupervisor: number; withJobTitle: number; withJobStatus: number } }>("/api/staff/stats");
}

export async function getStaff(id: number) {
  return apiRequest<{ data: StaffMember }>(`/api/staff/${id}`);
}

export async function listStaff(params = "") {
  return apiRequest<{ data: StaffMember[]; meta: Record<string, unknown> }>(`/api/staff${params}`);
}

export async function updateStaff(id: number, input: StaffUpdateInput) {
  return apiRequest<{ data: StaffMember }>(`/api/staff/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

// Notifications
export async function getNotifications() {
  return apiRequest<{ data: AppNotification[] }>("/api/notifications");
}

export async function getUnreadCount() {
  return apiRequest<{ data: { count: number } }>("/api/notifications/unread-count");
}

export async function markNotificationRead(id: string) {
  return apiRequest<{ data: { success: boolean } }>(`/api/notifications/${id}/read`, { method: "POST" });
}

export async function markAllNotificationsRead() {
  return apiRequest<{ data: { success: boolean } }>("/api/notifications/read-all", { method: "POST" });
}

// Corrections
export async function listCorrections(params = "") {
  return apiRequest<{ data: AttendanceCorrection[]; meta: Record<string, unknown> }>(`/api/corrections${params}`);
}

export async function requestCorrection(input: AttendanceCorrectionInput) {
  return apiRequest<{ data: AttendanceCorrection }>("/api/corrections", { method: "POST", body: JSON.stringify(input) });
}

export async function approveCorrection(id: number) {
  return apiRequest<{ data: AttendanceCorrection }>(`/api/corrections/${id}/approve`, { method: "POST" });
}

export async function rejectCorrection(id: number, rejectionNote: string) {
  return apiRequest<{ data: AttendanceCorrection }>(`/api/corrections/${id}/reject`, {
    method: "POST",
    body: JSON.stringify({ rejection_note: rejectionNote }),
  });
}

export async function getStaffTransfers(userId: number) {
  return apiRequest<{ data: DivisionTransfer[] }>(`/api/staff/${userId}/transfers`);
}
