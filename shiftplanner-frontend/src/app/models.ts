export interface User {
  id: string;
  fname: string;
  lname: string;
  employmentDate: Date;
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
  id: string;
  label: string;
}

export interface Assignment {
  userId: string;
  date: string;
  shiftId: string;
}
