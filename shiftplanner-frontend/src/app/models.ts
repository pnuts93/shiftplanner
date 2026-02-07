export interface User {
  id: number;
  fname: string;
  lname: string;
  employmentDate: string;
  hasSpecialization: boolean;
  locale: string;
  email: string;
  role?: string;
  isCounted: boolean;
  isNotified: boolean;
}

export interface UserProfile {
  email: string;
  fname: string;
  lname: string;
  employmentDate: string;
  hasSpecialization: boolean;
  isNotified: boolean;
  locale: string;
  oldPassword?: string;
  newPassword?: string;
}

export interface ApprovedUser {
  email: string;
  isAdmin: boolean;
  isCounted: boolean;
}

export interface ShiftOption {
  id: number;
  shiftStart?: number;
  shiftEnd?: number;
  name: string;
  displayName: string;
  maxWorkers: number;
  minExperiencedWorkers: number;
  maxExperiencedWorkers: number;
  isWorkingShift: boolean;
}

export interface Configuration {
  shifts: ShiftOption[];
  experiencedYearsThreshold: number;
  maxMonthOffset: number;
}

export interface Assignment {
  userId: number;
  date: string;
  shiftId: number;
  isMarkedImportant: boolean;
  userComment: string;
}

export interface AssignmentUpdate {
  assignment: Assignment;
  updateType: 'update' | 'comment' | 'important';
}

export interface CommentDialogData {
  comment: string;
  canEdit: boolean;
}

export interface MonthBlocker {
  year: number;
  month: number;
}

export type RequestState = 'idle' | 'loading' | 'success' | 'error';
