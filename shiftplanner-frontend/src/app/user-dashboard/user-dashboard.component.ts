import { Component, inject, OnInit } from '@angular/core';
import {
  Assignment,
  AssignmentUpdate,
  Configuration,
  ShiftOption,
  User,
} from '../models';
import { ShiftService } from '../shift.service';
import {
  MatDatepicker,
  MatDatepickerModule,
} from '@angular/material/datepicker';
import { MatFormFieldModule } from '@angular/material/form-field';

import { FormsModule } from '@angular/forms';
import { UserShiftTableComponent } from '../user-shift-table/user-shift-table.component';
import { MatInputModule } from '@angular/material/input';
import {
  TranslateModule,
  TranslatePipe,
  TranslateService,
} from '@ngx-translate/core';
import { UserService } from '../user.service';
import { Observable, of } from 'rxjs';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ConfigurationService } from '../configuration.service';
import { emptyConfig } from '../utils';
import { P } from '@angular/cdk/keycodes';
import { ConflictError, LockedError } from '../errors';

@Component({
  selector: 'app-user-dashboard',
  standalone: true,
  imports: [
    FormsModule,
    MatFormFieldModule,
    MatDatepickerModule,
    MatInputModule,
    TranslateModule,
    TranslatePipe,
    UserShiftTableComponent,
  ],
  providers: [MatDatepickerModule],
  templateUrl: './user-dashboard.component.html',
  styleUrl: './user-dashboard.component.css',
})
export class UserDashboardComponent implements OnInit {
  users: User[] = [];
  configuration$: Observable<Configuration> = of(emptyConfig());
  shiftAssignments$: Observable<Record<number, Record<string, Assignment>>> =
    of({});
  selectedDate: Date;
  daysInMonth: string[] = [];
  minDate: Date = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
  maxDate: Date = new Date(new Date().getFullYear(), new Date().getMonth(), 0);
  private snackbar = inject(MatSnackBar);

  constructor(
    private configurationService: ConfigurationService,
    private shiftService: ShiftService,
    private translate: TranslateService,
    private userService: UserService,
  ) {
    let tmpDate = new Date();
    this.selectedDate = new Date(tmpDate.getFullYear(), tmpDate.getMonth(), 1);
    this.userService.getUsers().subscribe((users) => (this.users = users));
    this.configuration$ = this.configurationService.getConfiguration();
    this.configuration$.subscribe((config) => {
      this.maxDate = new Date(
        new Date().getFullYear(),
        new Date().getMonth() + config.maxMonthOffset,
        0,
      );
    });
  }

  ngOnInit() {
    this.loadData();
  }

  onMonthSelected(date: Date, datepicker: MatDatepicker<Date>) {
    if (
      date.getMonth() === this.selectedDate.getMonth() &&
      date.getFullYear() === this.selectedDate.getFullYear()
    ) {
      datepicker.close();
      return;
    }
    this.selectedDate = new Date(date.getFullYear(), date.getMonth(), 1);
    datepicker.close();
    this.loadData();
  }

  loadData(force: boolean = false) {
    const [year, month] = [
      this.selectedDate.getFullYear(),
      this.selectedDate.getMonth(),
    ];
    this.shiftAssignments$ = this.shiftService.getAssignments(
      year,
      month,
      force,
    );
    this.daysInMonth = this.generateDaysInMonth(year, month);
  }

  generateDaysInMonth(year: number, month: number): string[] {
    month++; // Adjust month to 1-based index
    const days = new Date(year, month, 0).getDate();
    return Array.from({ length: days }, (_, i) => `${year}-${month}-${i + 1}`);
  }

  onShiftUpdate(assignment: AssignmentUpdate) {
    let messageKey = '';
    this.triggerAssignmentRequest(assignment).catch((error) => {
      if (error instanceof ConflictError) {
        messageKey = 'user_dashboard.update_conflict';
      } else if (error instanceof LockedError) {
        messageKey = 'user_dashboard.update_locked';
      } else {
        messageKey = 'user_dashboard.update_failed';
      }
      this.snackbar.open(this.translate.instant(messageKey), undefined, {
        duration: 3000,
      });
      this.loadData(true);
    });
  }

  async triggerAssignmentRequest(assignment: AssignmentUpdate): Promise<void> {
    if (assignment.updateType === 'update') {
      if (assignment.assignment.shiftId === 0) {
        return this.shiftService.deleteAssignment(assignment.assignment);
      } else {
        return this.shiftService.updateAssignment(assignment.assignment);
      }
    } else if (
      assignment.updateType === 'important' ||
      assignment.updateType === 'comment'
    ) {
      return this.shiftService.updateAssignmentAttribute(assignment.assignment);
    }
  }
}
