import { CommonModule } from '@angular/common';
import {
  ChangeDetectorRef,
  Component,
  EventEmitter,
  Input,
  OnChanges,
  Output,
} from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatTableModule } from '@angular/material/table';
import { Assignment, User } from '../models';
import { MatSelectModule } from '@angular/material/select';
import TranslationEN from '../../../public/i18n/en.json';
import TranslationDE from '../../../public/i18n/de.json';
import {
  TranslateModule,
  TranslatePipe,
  TranslateService,
} from '@ngx-translate/core';
import { AuthService } from '../auth.service';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-user-shift-table',
  imports: [
    CommonModule,
    FormsModule,
    MatFormFieldModule,
    MatSelectModule,
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
  @Input({ required: true }) shifts: { id: string; label: string }[] = [];
  @Input({ required: true }) assignments: Record<
    string,
    Record<string, string>
  > = {};
  currentUser: User | null = null;
  userRole: string | null = null;
  @Output() shiftSelectionEvent = new EventEmitter<Assignment>();

  headers: string[] = [];

  constructor(
    private translate: TranslateService,
    private authService: AuthService
  ) {
    this.translate.addLangs(['de', 'en']);
    this.translate.setTranslation('en', TranslationEN);
    this.translate.setTranslation('de', TranslationDE);
    this.translate.setDefaultLang(environment.defaultLocale);
    this.authService.user$.subscribe((user) => {
      if (user) {
        this.currentUser = user;
        this.userRole = user.role ?? null;
        this.translate.use(user.locale);
      }
    });
  }

  onSelect(userId: string, date: string, shiftId: string) {
    this.shiftSelectionEvent.emit({ userId, date, shiftId });
  }

  ngOnChanges() {
    this.headers = ['Name', ...this.days];
  }

  getShiftCounts(shiftId: string): Record<string, number> {
    const counts: Record<string, number> = {};

    for (const day of this.days) {
      let count = 0;
      for (const user of this.users) {
        const userShifts = this.assignments[user.id];
        if (userShifts && userShifts[day] === shiftId) {
          count++;
        }
      }
      counts[day] = count;
    }

    return counts;
  }

  getNumberExperienced(day: string, shift: string): number {
    let yearIndex: number = +day.substring(0, day.indexOf('-'));
    let monthIndex: number = +day.substring(
      day.indexOf('-') + 1,
      day.lastIndexOf('-')
    );
    let dayIndex: number = +day.substring(day.lastIndexOf('-') + 1);
    let targetDate: Date = new Date(yearIndex - 5, monthIndex, dayIndex);
    let count = 0;
    for (const user of this.users) {
      if (user.employmentDate > targetDate && !user.hasSpecialization) {
        continue;
      }
      const userShifts = this.assignments[user.id];
      if (userShifts && userShifts[day] === shift) {
        count++;
      }
    }
    return count;
  }

  isWorkingShift(shiftId: string): boolean {
    return shiftId === '1' || shiftId === '2' || shiftId === '3';
  }

  canEditShift(userId: string) {
    if (this.userRole === 'admin') {
      return true;
    }
    if (this.userRole === 'user' && this.currentUser) {
      return userId === this.currentUser.id;
    }
    return false;
  }
}
