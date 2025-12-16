import { Component, inject, OnInit } from '@angular/core';
import { Assignment, ShiftOption, User } from '../models';
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
  shiftOptions$: Observable<ShiftOption[]> = of([]);
  shiftAssignments$: Observable<Record<number, Record<string, number>>> = of(
    {}
  );
  selectedDate: Date;
  daysInMonth: string[] = [];
  minDate: Date = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
  maxDate: Date = new Date(
    new Date().getFullYear() + 1,
    new Date().getMonth(),
    0
  );
  private snackbar = inject(MatSnackBar);

  constructor(
    private shiftService: ShiftService,
    private translate: TranslateService,
    private userService: UserService
  ) {
    let tmpDate = new Date();
    this.selectedDate = new Date(tmpDate.getFullYear(), tmpDate.getMonth(), 1);
  }

  ngOnInit() {
    this.loadData();
  }

  onMonthSelected(date: Date, datepicker: MatDatepicker<Date>) {
    console.log('Selected month:', date);
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
    this.daysInMonth = this.generateDaysInMonth(year, month);
    this.userService.getUsers().subscribe((users) => (this.users = users));
    this.shiftOptions$ = this.shiftService.getShiftOptions();
    this.shiftAssignments$ = this.shiftService.getAssignments(
      year,
      month,
      force
    );
  }

  generateDaysInMonth(year: number, month: number): string[] {
    month++; // Adjust month to 1-based index
    const days = new Date(year, month, 0).getDate();
    return Array.from({ length: days }, (_, i) => `${year}-${month}-${i + 1}`);
  }

  onShiftUpdate(assignment: Assignment) {
    this.shiftService.updateAssignment(assignment).catch((_) => {
      this.snackbar.open(
        this.translate.instant('user_dashboard.update_failed'),
        undefined,
        { duration: 3000 }
      );
      this.loadData(true);
    });
  }
}
