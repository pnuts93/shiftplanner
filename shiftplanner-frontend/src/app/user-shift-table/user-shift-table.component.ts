import { CommonModule } from '@angular/common';
import {
  Component,
  EventEmitter,
  inject,
  Input,
  OnChanges,
  Output,
  signal,
} from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatTableModule } from '@angular/material/table';
import {
  Assignment,
  AssignmentUpdate,
  Configuration,
  MonthBlocker,
  ShiftOption,
  User,
} from '../models';
import { MatSelectModule } from '@angular/material/select';
import { TranslateModule, TranslatePipe } from '@ngx-translate/core';
import { AuthService } from '../auth.service';
import { Observable, of } from 'rxjs';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { emptyConfig } from '../utils';
import { MatIcon } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { CommentDialogComponent } from '../comment-dialog/comment-dialog.component';
import { MonthBlockerService } from '../month-blocker.service';

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
    MatIcon,
    MatButtonModule,
  ],
  templateUrl: './user-shift-table.component.html',
  styleUrl: './user-shift-table.component.css',
})
export class UserShiftTableComponent implements OnChanges {
  @Input({ required: true }) users: User[] = [];
  @Input({ required: true }) days: string[] = [];
  @Input({ required: true }) configuration$: Observable<Configuration> =
    of(emptyConfig());
  @Input({ required: true }) assignmentsObservable: Observable<
    Record<number, Record<string, Assignment>>
  > = of({});
  currentUser: User | null = null;
  userRole: string | null = null;
  /* UserId -> Date -> Assignment */
  currentAssignments: Record<number, Record<string, Assignment>> = {};
  /* Date -> ShiftId -> Count */
  experiencedShiftCount: Record<string, Record<number, number>> = {};
  /* ShiftId -> Date -> Count | different from experienced shift count due to data retrieval in table */
  shiftCount: Record<number, Record<string, number>> = {};
  experiencedYearsThreshold: number = 5; // default
  @Output() shiftSelectionEvent = new EventEmitter<AssignmentUpdate>();
  shiftsCache = signal<Map<string, ShiftOption[]>>(new Map());
  headers: string[] = [];
  shifts: ShiftOption[] = [];
  monthBlockers$: Observable<MonthBlocker[]>;
  monthBlockers: MonthBlocker[] = [];
  currentComment = signal<string>('');
  dialog = inject(MatDialog);

  constructor(
    private authService: AuthService,
    private monthBlockerService: MonthBlockerService,
  ) {
    this.authService.user$.subscribe((user) => {
      if (user) {
        this.currentUser = user;
        this.userRole = user.role ?? null;
      }
    });
    this.monthBlockers$ = this.monthBlockerService.getMonthBlockers();
    this.monthBlockers$.subscribe((blockers) => {
      this.monthBlockers = blockers;
      this.checkIfCurrentMonthBlocked(blockers);
    });
  }

  openCommentDialog(userId: number, date: string, canEdit: boolean): void {
    const assignment = this.currentAssignments[userId][date];
    if (assignment && assignment.userComment) {
      this.currentComment.set(assignment.userComment);
    } else {
      this.currentComment.set('');
    }
    const dialogRef = this.dialog.open(CommentDialogComponent, {
      data: { comment: this.currentComment(), canEdit: canEdit },
    });
    dialogRef.afterClosed().subscribe((result) => {
      if (result !== undefined && this.currentComment() !== result) {
        this.currentAssignments[userId][date].userComment = result;
        this.shiftSelectionEvent.emit({
          assignment: this.currentAssignments[userId][date],
          updateType: 'comment',
        });
      }
    });
  }

  onSelect(user: User, date: string, shiftId: number) {
    let oldAssignment = this.currentAssignments[user.id][date];
    this.currentAssignments[user.id][date] = {
      shiftId: shiftId,
      date: date,
      userId: user.id,
      isMarkedImportant: false,
      userComment: '',
    };
    if (oldAssignment.shiftId === shiftId || !user.isCounted) {
      return;
    } else if (this.isUserExperienced(user, this.parseDate(date))) {
      if (this.experiencedShiftCount[date][oldAssignment.shiftId] > 0) {
        this.experiencedShiftCount[date][oldAssignment.shiftId]--;
      }
      this.experiencedShiftCount[date][shiftId]++;
    }
    if (
      this.shifts.find((s) => s.id === oldAssignment.shiftId)?.isWorkingShift &&
      this.shiftCount[oldAssignment.shiftId][date] > 0
    ) {
      this.shiftCount[oldAssignment.shiftId][date]--;
    }
    if (this.shifts.find((s) => s.id === shiftId)?.isWorkingShift) {
      this.shiftCount[shiftId][date]++;
    }
    this.shiftSelectionEvent.emit({
      assignment: this.currentAssignments[user.id][date],
      updateType: 'update',
    });
  }

  ngOnChanges() {
    this.headers = ['Name', ...this.days];
    this.users = [...this.users];
    this.assignmentsObservable.subscribe((assignments) => {
      this.currentAssignments = assignments;
      this.calculateExperiencedShiftCount();
      this.calculateShiftCount();
    });
    this.configuration$.subscribe((configuration) => {
      this.shifts = [...this.getDefaultShiftOptions(), ...configuration.shifts];
      this.experiencedYearsThreshold = configuration.experiencedYearsThreshold;
      this.calculateExperiencedShiftCount();
      this.calculateShiftCount();
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
        if (userShifts && userShifts[day]?.shiftId === shiftId) {
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
      if (!shift.isWorkingShift) {
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
      if (userShifts && userShifts[stringDate]?.shiftId === shift) {
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
        if (!shift.isWorkingShift) {
          continue;
        }
        this.experiencedShiftCount[day][shift.id] =
          this.getNumberExperiencedShift(day, shift.id);
      }
    }
  }

  canEditShift(userId: number) {
    if (this.checkIfCurrentMonthBlocked(this.monthBlockers)) {
      return false;
    }
    if (this.userRole === 'admin') {
      return true;
    }
    if (this.userRole === 'user' && this.currentUser) {
      return userId === this.currentUser.id;
    }
    return false;
  }

  canEditShiftAttributes(userId: number, day: string) {
    if (this.checkIfCurrentMonthBlocked(this.monthBlockers)) {
      return false;
    }
    const assignment = this.currentAssignments[userId][day];
    return (
      userId === this.currentUser?.id && assignment && assignment.shiftId !== 0
    );
  }

  isRecordEmpty(record: Record<number, Record<string, Assignment>>): boolean {
    let result = Object.keys(record).length === 0;
    return result;
  }

  isUserExperienced(user: User, date: Date): boolean {
    let employmentDate = new Date(user.employmentDate);
    let targetDate = new Date(
      date.getFullYear() - this.experiencedYearsThreshold,
      date.getMonth(),
      date.getDate(),
    );
    return employmentDate <= targetDate || user.hasSpecialization;
  }

  isUserExperiencedMonthStart(user: User): boolean {
    let monthStart = this.parseDate(this.days[0]);
    return this.isUserExperienced(user, monthStart);
  }

  parseDate(date: string): Date {
    let yearIndex: number = +date.substring(0, date.indexOf('-'));
    let monthIndex: number = +date.substring(
      date.indexOf('-') + 1,
      date.lastIndexOf('-'),
    );
    let dayIndex: number = +date.substring(date.lastIndexOf('-') + 1);
    return new Date(yearIndex, monthIndex, dayIndex);
  }

  onOpenShiftOption(user: User, date: string): void {
    const key = `${user.id}-${date}`;
    const cache = this.shiftsCache();

    const shifts = this.getAvailableShifts(user, date);
    cache.set(key, shifts);
    this.shiftsCache.set(new Map(cache));
  }

  getShiftsForSelect(user: User, date: string): ShiftOption[] {
    const key = `${user.id}-${date}`;
    return this.shiftsCache().get(key) || this.shifts;
  }

  getAvailableShifts(user: User, date: string): ShiftOption[] {
    return this.shifts.filter((shift) => {
      if (
        !shift.isWorkingShift ||
        !user.isCounted ||
        this.currentAssignments[user.id]?.[date]?.shiftId === shift.id
      ) {
        return true;
      }
      return (
        this.shiftCount[shift.id][date] < shift.maxWorkers &&
        ((!this.isUserExperienced(user, this.parseDate(date)) &&
          (this.experiencedShiftCount[date][shift.id] >=
            shift.minExperiencedWorkers ||
            this.shiftCount[shift.id][date] <
              shift.maxWorkers - shift.minExperiencedWorkers)) ||
          (this.isUserExperienced(user, this.parseDate(date)) &&
            this.experiencedShiftCount[date][shift.id] <
              shift.maxExperiencedWorkers))
      );
    });
  }

  getDefaultShiftOptions(): ShiftOption[] {
    return [
      {
        id: 0,
        name: 'none',
        isWorkingShift: false,
        displayName: '-- Choose Shift --',
        maxExperiencedWorkers: 0,
        maxWorkers: 0,
        minExperiencedWorkers: 0,
      },
    ];
  }

  isHoliday(date: string): boolean {
    const dayOfWeek = this.parseDate(date).getDay();
    return dayOfWeek === 0 || dayOfWeek === 6;
  }

  isMarkedImportant(userId: number, date: string): boolean {
    const userAssignments = this.currentAssignments[userId];
    if (
      userAssignments &&
      userAssignments[date] &&
      userAssignments[date].isMarkedImportant
    ) {
      return true;
    }
    return false;
  }

  isCommented(userId: number, date: string): boolean {
    const userAssignments = this.currentAssignments[userId];
    if (
      userAssignments &&
      userAssignments[date] &&
      userAssignments[date].userComment &&
      userAssignments[date].userComment.trim().length > 0
    ) {
      return true;
    }
    return false;
  }

  toggleImportant(userId: number, date: string) {
    const assignment = this.currentAssignments[userId][date];
    this.currentAssignments[userId][date].isMarkedImportant =
      !assignment.isMarkedImportant;
    this.shiftSelectionEvent.emit({
      assignment: this.currentAssignments[userId][date],
      updateType: 'important',
    });
  }

  checkIfCurrentMonthBlocked(monthBlockers: MonthBlocker[]): boolean {
    if (this.days.length === 0) {
      return false;
    }
    const date = this.parseDate(this.days[0]);
    return monthBlockers.some(
      (blocker) =>
        blocker.year === date.getFullYear() &&
        blocker.month === date.getMonth(),
    );
  }
}
