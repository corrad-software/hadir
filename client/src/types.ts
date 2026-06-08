export type ThemeColor = "violet" | "blue" | "green" | "red" | "black-white" | "grey";

export type ApiError = { error: { code: string; message: string; details?: unknown } };

export type ApiResponse<T> = { data: T; meta?: Record<string, unknown> };

export type User = {
  id: number;
  email: string;
  name: string;
  photoUrl?: string;
  role?: string;
  hasSupervisees?: boolean;
};

export type SettingsPayload = {
  siteTitle: string;
  tagline: string;
  webfrontTitle: string;
  webfrontTagline: string;
  titleFormat: string;
  metaDescription: string;
  siteIconUrl: string;
  webfrontLogoUrl: string;
  sidebarLogoUrl: string;
  faviconUrl: string;
  language: string;
  timezone: string;
  footerText: string;
  frontPageId: number | null;
};

export type Role = {
  id: number;
  name: string;
  description: string;
  permissions: string[];
  createdAt: string;
  updatedAt: string;
};

export type RoleInput = {
  name: string;
  description: string;
  permissions: string[];
};

export type UserDetail = {
  id: number;
  name: string;
  email: string;
  role: string;
  isActive: boolean;
  photoUrl: string | null;
  divisionId: number | null;
  divisionName: string | null;
  supervisorId: number | null;
  supervisorName: string | null;
  createdAt: string;
  updatedAt: string;
};

export type UserInput = {
  name: string;
  email: string;
  password?: string;
  role: string;
  isActive: boolean;
};

export type AuditLog = {
  id: number;
  userId: number | null;
  action: string;
  auditableType: string | null;
  auditableId: number | null;
  oldValues: Record<string, unknown> | null;
  newValues: Record<string, unknown> | null;
  ipAddress: string | null;
  userAgent: string | null;
  createdAt: string;
  user?: { id: number; name: string; email: string } | null;
};

export type AttendanceStatus = 'on_time' | 'late' | 'early_leave' | 'absent' | 'pending';

export type AttendanceApprovalStatus = 'pending_approval' | 'approved' | 'rejected';

export type AttendancePolicyInput = {
  name: string;
  work_days: number[];
  start_time: string;
  end_time: string;
  grace_period_minutes?: number;
  is_active?: boolean;
};

export type AttendancePolicy = {
  id: number;
  name: string;
  workDays: number[];
  startTime: string;
  endTime: string;
  gracePeriodMinutes: number;
  officesCount?: number;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
};

export type OfficeInput = {
  name: string;
  latitude: number | null;
  longitude: number | null;
  radiusMeters: number;
  policyId: number | null;
};

export type Office = {
  id: number;
  name: string;
  latitude: number;
  longitude: number;
  radiusMeters: number;
  policyId: number;
  policyName: string | null;
  createdAt: string;
  updatedAt: string;
};

export type AttendanceLog = {
  id: number;
  userId: number;
  workDate: string;
  checkInAt: string | null;
  checkInLatitude: number | null;
  checkInLongitude: number | null;
  checkInWithinRadius: boolean | null;
  checkOutAt: string | null;
  checkOutLatitude: number | null;
  checkOutLongitude: number | null;
  checkOutWithinRadius: boolean | null;
  status: AttendanceStatus;
  notes: string | null;
  approvalStatus: AttendanceApprovalStatus | null;
  approvedBy: number | null;
  approvedAt: string | null;
  rejectionReason: string | null;
  createdAt: string;
  updatedAt: string;
  user?: { id: number; name: string; email: string } | null;
};

export type CheckInInput = {
  latitude?: number | null;
  longitude?: number | null;
};

export type AttendanceLogUpdateInput = {
  status?: AttendanceStatus;
  notes?: string;
};

export type TeamMemberAttendance = {
  id: number;
  name: string;
  photoUrl: string | null;
  checkedIn: boolean;
  checkedOut: boolean;
  status: AttendanceStatus | "absent";
  checkInAt: string | null;
  checkOutAt: string | null;
  latitude: number | null;
  longitude: number | null;
  withinRadius: boolean | null;
};

export type AttendanceReportRow = {
  workDate: string;
  onTime: number;
  late: number;
  earlyLeave: number;
  absent: number;
  pending: number;
  total: number;
};

export type DivisionInput = {
  name: string;
  parentId?: number | null;
  attendancePolicyId?: number | null;
};

export type Division = {
  id: number;
  name: string;
  parentId: number | null;
  attendancePolicyId: number | null;
  childrenCount: number;
  usersCount: number;
  children?: Division[];
  createdAt: string;
  updatedAt: string;
};

export type DivisionTransfer = {
  id: number;
  userId: number;
  fromDivisionId: number | null;
  toDivisionId: number;
  toDivisionName?: string | null;
  effectiveDate: string;
  processed: boolean;
  createdAt: string;
};

export type JobStatusInput = { name: string };
export type JobStatus = JobStatusInput & { id: number; createdAt: string; updatedAt: string };

export type JobTitleInput = { name: string };
export type JobTitle = JobTitleInput & { id: number; createdAt: string; updatedAt: string };

export type StaffMember = {
  id: number;
  name: string;
  email: string;
  role: string;
  isActive: boolean;
  photoUrl: string | null;
  dob: string | null;
  phone: string | null;
  sex: 'male' | 'female' | 'other' | null;
  jobTitleId: number | null;
  jobTitleName: string | null;
  jobStatusId: number | null;
  jobStatusName: string | null;
  addressLine1: string | null;
  addressLine2: string | null;
  addressTownship: string | null;
  addressPostcode: string | null;
  addressState: string | null;
  officeId: number | null;
  officeName: string | null;
  divisionId: number | null;
  divisionName: string | null;
  supervisorId: number | null;
  supervisorName: string | null;
  pendingTransfer?: DivisionTransfer | null;
};

export type AppNotification = {
  id: string;
  type: string;
  title: string;
  body: string;
  url: string | null;
  read: boolean;
  createdAt: string;
};

export type CorrectionStatus = "pending" | "approved" | "rejected";

export type AttendanceCorrectionInput = {
  attendanceLogId: number;
  correctedCheckInAt?: string | null;
  correctedCheckOutAt?: string | null;
  reason: string;
};

export type AttendanceCorrection = {
  id: number;
  attendanceLogId: number;
  userId: number;
  userName: string;
  workDate: string;
  originalCheckInAt: string | null;
  originalCheckOutAt: string | null;
  correctedCheckInAt: string | null;
  correctedCheckOutAt: string | null;
  reason: string;
  status: CorrectionStatus;
  reviewedBy: number | null;
  reviewedByName: string | null;
  reviewedAt: string | null;
  rejectionNote: string | null;
  createdAt: string;
};

export type StaffUpdateInput = {
  name?: string;
  dob?: string | null;
  phone?: string | null;
  sex?: string | null;
  jobTitleId?: number | null;
  jobStatusId?: number | null;
  addressLine1?: string | null;
  addressLine2?: string | null;
  addressTownship?: string | null;
  addressPostcode?: string | null;
  addressState?: string | null;
  officeId?: number | null;
  divisionId?: number | null;
  supervisorId?: number | null;
};
