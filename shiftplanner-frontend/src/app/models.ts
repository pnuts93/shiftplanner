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
  name: string;
  display: string;
  isWorking: boolean;
}

export interface Configuration {
  shifts: ShiftOption[];
  experiencedYearsThreshold: number;
  maxPeoplePerShift: number;
  minExpertsPerShift: number;
}

export interface Assignment {
  userId: number;
  date: string;
  shiftId: number;
}

export type RequestState = 'idle' | 'loading' | 'success' | 'error';
