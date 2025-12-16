import { CommonModule } from '@angular/common';
import {
  Component,
  EventEmitter,
  Input,
  OnChanges,
  Output,
} from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatTableModule } from '@angular/material/table';
import { Assignment, ShiftOption, User } from '../models';
import { MatSelectModule } from '@angular/material/select';
import { TranslateModule, TranslatePipe } from '@ngx-translate/core';
import { AuthService } from '../auth.service';
import { Observable, of } from 'rxjs';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';

@Component({
  selector: 'app-user-shift-table',
  imports: [
    CommonModule,
    FormsModule,
    MatFormFieldModule,
    MatSelectModule,
    MatProgressSpinnerModule,
    MatTableModule,
    TranslateModule,
    TranslatePipe,
  ],
  templateUrl: './user-shift-table.component.html',
  styleUrl: './user-shift-table.component.css',
})
export class UserShiftTableComponent implements OnChanges {
  @Input({ required: true }) users: User[] = [];
  @Input({ required: true }) days: string[] = [];
  @Input({ required: true }) shiftOptions$: Observable<ShiftOption[]> = of([]);
  @Input({ required: true }) assignmentsObservable: Observable<
    Record<number, Record<string, number>>
  > = of({});
  currentUser: User | null = null;
  userRole: string | null = null;
  /* UserId -> Date -> ShiftId */
  currentAssignments: Record<number, Record<string, number>> = {};
  /* Date -> ShiftId -> Count */
  experiencedShiftCount: Record<string, Record<number, number>> = {};
  /* ShiftId -> Date -> Count | different from experienced shift count due to data retrieval in table */
  shiftCount: Record<number, Record<string, number>> = {};
  experiencedYearsThreshold: number = 5; // Years of experience to be considered experienced
  @Output() shiftSelectionEvent = new EventEmitter<Assignment>();

  headers: string[] = [];
  shifts: ShiftOption[] = [];

  constructor(private authService: AuthService) {
    this.authService.user$.subscribe((user) => {
      if (user) {
        this.currentUser = user;
        this.userRole = user.role ?? null;
      }
    });
  }

  onSelect(user: User, date: string, shiftId: number) {
    let oldShiftId = this.currentAssignments[user.id][date];
    this.currentAssignments[user.id][date] = shiftId;
    if (oldShiftId === shiftId || !user.isCounted) {
      return;
    } else if (this.isUserExperienced(user, this.parseDate(date))) {
      if (this.experiencedShiftCount[date][oldShiftId] > 0) {
        this.experiencedShiftCount[date][oldShiftId]--;
      }
      this.experiencedShiftCount[date][shiftId]++;
    }
    if (
      this.shifts.find((s) => s.id === oldShiftId)?.isWorking &&
      this.shiftCount[oldShiftId][date] > 0
    ) {
      this.shiftCount[oldShiftId][date]--;
    }
    if (this.shifts.find((s) => s.id === shiftId)?.isWorking) {
      this.shiftCount[shiftId][date]++;
    }
    this.shiftSelectionEvent.emit({ userId: user.id, date, shiftId });
  }

  ngOnChanges() {
    this.headers = ['Name', ...this.days];
    this.assignmentsObservable.subscribe((assignments) => {
      this.currentAssignments = assignments;
      this.calculateExperiencedShiftCount();
      this.calculateShiftCount();
    });
    this.shiftOptions$.subscribe((shiftOptions) => {
      this.shifts = shiftOptions;
    });
  }

  getShiftCounts(shiftId: number): Record<string, number> {
    const counts: Record<string, number> = {};

    for (const day of this.days) {
      let count = 0;
      for (const user of this.users) {
        if (!user.isCounted) {
          continue;
        }
        const userShifts = this.currentAssignments[user.id];
        if (userShifts && userShifts[day] === shiftId) {
          count++;
        }
      }
      counts[day] = count;
    }

    return counts;
  }

  calculateShiftCount() {
    this.shiftCount = {};
    for (const shift of this.shifts) {
      if (!shift.isWorking) {
        continue;
      }
      this.shiftCount[shift.id] = this.getShiftCounts(shift.id);
    }
  }

  getNumberExperiencedShift(stringDate: string, shift: number): number {
    let date = this.parseDate(stringDate);
    let count = 0;
    for (const user of this.users) {
      if (!this.isUserExperienced(user, date) || !user.isCounted) {
        continue;
      }
      const userShifts = this.currentAssignments[user.id];
      if (userShifts && userShifts[stringDate] === shift) {
        count++;
      }
    }
    return count;
  }

  calculateExperiencedShiftCount() {
    this.experiencedShiftCount = {};
    for (const day of this.days) {
      this.experiencedShiftCount[day] = {};
      for (const shift of this.shifts) {
        if (!shift.isWorking) {
          continue;
        }
        this.experiencedShiftCount[day][shift.id] =
          this.getNumberExperiencedShift(day, shift.id);
      }
    }
  }

  canEditShift(userId: number) {
    if (this.userRole === 'admin') {
      return true;
    }
    if (this.userRole === 'user' && this.currentUser) {
      return userId === this.currentUser.id;
    }
    return false;
  }

  isRecordEmpty(record: Record<number, Record<string, number>>): boolean {
    let result = Object.keys(record).length === 0;
    return result;
  }

  isUserExperienced(user: User, date: Date): boolean {
    let employmentDate = new Date(user.employmentDate);
    let targetDate = new Date(
      date.getFullYear() - this.experiencedYearsThreshold,
      date.getMonth(),
      date.getDate()
    );
    return employmentDate <= targetDate || user.hasSpecialization;
  }

  parseDate(date: string): Date {
    let yearIndex: number = +date.substring(0, date.indexOf('-'));
    let monthIndex: number = +date.substring(
      date.indexOf('-') + 1,
      date.lastIndexOf('-')
    );
    let dayIndex: number = +date.substring(date.lastIndexOf('-') + 1);
    return new Date(yearIndex, monthIndex, dayIndex);
  }

  getAvailableShifts(user: User, date: string): ShiftOption[] {
    return this.shifts.filter((shift) => {
      if (
        !shift.isWorking ||
        this.currentAssignments[user.id]?.[date] === shift.id
      ) {
        return true;
      }
      return (
        (this.shiftCount[shift.id][date] < 3 ||
          this.experiencedShiftCount[date][shift.id] > 0 ||
          this.isUserExperienced(user, this.parseDate(date))) &&
        this.shiftCount[shift.id][date] < 4
      );
    });
  }
}
