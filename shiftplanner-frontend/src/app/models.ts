export interface User {
  id: number;
  fname: string;
  lname: string;
  employmentDate: string;
  hasSpecialization: boolean;
  locale: string;
  email: string;
  role?: string;
}

export interface ApprovedUser {
  email: string;
  isAdmin: boolean;
}

export interface ShiftOption {
  id: number;
  label: string;
}

export interface Assignment {
  userId: number;
  date: string;
  shiftId: number;
}

export type RequestState = 'idle' | 'loading' | 'success' | 'error';
