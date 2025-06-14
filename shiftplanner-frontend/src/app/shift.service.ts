import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { ShiftOption, User } from './models';

@Injectable({
  providedIn: 'root',
})
export class ShiftService {
  private users = new BehaviorSubject<User[]>([]);
  private shiftOptions = new BehaviorSubject<ShiftOption[]>([]);
  /** UserId -> Date -> ShiftId */
  private assignments = new BehaviorSubject<
    Record<string, Record<string, string>>
  >({});
  constructor() {}

  getAllUsers(): Observable<User[]> {
    // Mock implementation
    if (this.users.getValue().length === 0) {
      this.users.next([
        { id: '1', fname: 'John', lname: 'Doe', employmentDate: new Date(2020, 1, 1), hasSpecialization: false, locale: 'en', email: 'jd@a.a'},
        { id: '2', fname: 'Jane', lname: 'Smith', employmentDate: new Date(2020, 1, 1), hasSpecialization: false, locale: 'en', email: 'js@a.a'},
        { id: '3', fname: 'Max', lname: 'Mustermann', employmentDate: new Date(2024, 1, 1), hasSpecialization: false, locale: 'de', email: 'mm@a.a'},
        { id: '4', fname: 'Erika', lname: 'Mustermann', employmentDate: new Date(2024, 1, 1), hasSpecialization: false, locale: 'de', email: 'em@a.a'},
        { id: '5', fname: 'Hans', lname: 'Müller', employmentDate: new Date(2024, 1, 1), hasSpecialization: false, locale: 'de', email: 'hm@a.a'},
        { id: '6', fname: 'Anna', lname: 'Schmidt', employmentDate: new Date(2024, 1, 1), hasSpecialization: false, locale: 'de', email: 'as@a.a'},
        { id: '7', fname: 'Peter', lname: 'Schneider', employmentDate: new Date(2024, 1, 1), hasSpecialization: false, locale: 'de', email: 'ps@a.a'},
        { id: '8', fname: 'Laura', lname: 'Fischer', employmentDate: new Date(2024, 1, 1), hasSpecialization: false, locale: 'de', email: 'lf@a.a'},
        { id: '9', fname: 'Michael', lname: 'Weber', employmentDate: new Date(2024, 1, 1), hasSpecialization: false, locale: 'de', email: 'mw@a.a'},
        { id: '10', fname: 'Sophie', lname: 'Hoffmann', employmentDate: new Date(2024, 1, 1), hasSpecialization: false, locale: 'de', email: 'sh@a.a'},
      ]);
    }
    return this.users.asObservable();
  }
  getShiftOptions(): Observable<ShiftOption[]> {
    // Mock implementation
    if (this.shiftOptions.getValue().length === 0) {
      this.shiftOptions.next([
        { id: '0', label: 'none' },
        { id: '1', label: 'early' },
        { id: '2', label: 'late' },
        { id: '3', label: 'night' },
        { id: '4', label: 'vacation' },
        { id: '5', label: 'training' },
      ]);
    }
    return this.shiftOptions.asObservable();
  }

  getAssignments(
    year: number,
    month: number
  ): Observable<Record<string, Record<string, string>>> {
    month++; // Adjust month to 1-based index
    // Mock implementation
    if (Object.keys(this.assignments.getValue()).length === 0) {
      const assignments: Record<string, Record<string, string>> = {};
      const daysInMonth = new Date(year, month, 0).getDate();
      for (let userId = 1; userId <= 10; userId++) {
        if (!assignments[userId]) {
          assignments[userId] = {};
        }
        for (let day = 1; day <= daysInMonth; day++) {
          const date = `${year}-${month}-${day}`;
          assignments[userId][date] = '0';
        }
      }
      this.assignments.next(assignments);
    }
    return this.assignments.asObservable();
  }

  updateAssignment(userId: string, date: string, shiftId: string) {
    if (
      this.assignments.getValue()[userId] &&
      this.assignments.getValue()[userId][date] !== shiftId
    ) {
      this.assignments.getValue()[date][userId] = shiftId;
    }
  }
}
