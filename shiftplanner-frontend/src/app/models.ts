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
}

export interface UserProfile {
  email: string;
  fname: string;
  lname: string;
  employmentDate: string;
  hasSpecialization: boolean;
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
  name: string;
  display: string;
  isWorking: boolean;
}

export interface Assignment {
  userId: number;
  date: string;
  shiftId: number;
}

export type RequestState = 'idle' | 'loading' | 'success' | 'error';
