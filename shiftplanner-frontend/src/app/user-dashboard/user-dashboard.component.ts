import { Component, OnInit } from '@angular/core';
import { Assignment, ShiftOption, User } from '../models';
import { ShiftService } from '../shift.service';
import {
  MatDatepicker,
  MatDatepickerModule,
} from '@angular/material/datepicker';
import { MatFormFieldModule } from '@angular/material/form-field';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { UserShiftTableComponent } from '../user-shift-table/user-shift-table.component';
import { MatInputModule } from '@angular/material/input';
import moment, { Moment } from 'moment';
import {
  TranslateModule,
  TranslatePipe,
  TranslateService,
} from '@ngx-translate/core';
import TranslationEN from '../../../public/i18n/en.json';
import TranslationDE from '../../../public/i18n/de.json';
import { AuthService } from '../auth.service';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-user-dashboard',
  standalone: true,
  imports: [
    CommonModule,
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
  user: User | null = null;
  shiftOptions: ShiftOption[] = [];
  shiftAssignments: Record<string, Record<string, string>> = {};
  selectedDate = moment();
  daysInMonth: string[] = [];
  minDate: Date = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
  maxDate: Date = new Date(
    new Date().getFullYear() + 1,
    new Date().getMonth(),
    0
  );

  constructor(
    private shiftService: ShiftService,
    private translate: TranslateService,
    private authService: AuthService
  ) {
    this.translate.addLangs(['de', 'en']);
    this.translate.setTranslation('en', TranslationEN);
    this.translate.setTranslation('de', TranslationDE);
    this.translate.setDefaultLang(environment.defaultLocale);
    this.authService.user$.subscribe((user) => {
      if (user) {
        this.user = user;
        this.translate.use(user.locale);
      }
    });
  }

  ngOnInit() {
    this.loadData();
  }

  onMonthSelected(date: Moment, datepicker: MatDatepicker<Date>) {
    this.selectedDate = date;
    datepicker.close();
    this.loadData();
  }

  loadData() {
    const [year, month] = [this.selectedDate.year(), this.selectedDate.month()];
    this.daysInMonth = this.generateDaysInMonth(year, month);
    this.shiftService.getAllUsers().subscribe((users) => (this.users = users));
    this.shiftService
      .getShiftOptions()
      .subscribe((options) => (this.shiftOptions = options));
    this.shiftService.getAssignments(year, month).subscribe((assignments) => {
      this.shiftAssignments = assignments;
    });
  }

  generateDaysInMonth(year: number, month: number): string[] {
    const days = new Date(year, month + 1, 0).getDate();
    return Array.from(
      { length: days },
      (_, i) => `${year}-${month + 1}-${i + 1}`
    );
  }

  onShiftUpdate(assignment: Assignment) {
    this.shiftService.updateAssignment(
      assignment.userId,
      assignment.date,
      assignment.shiftId
    );
  }
}
