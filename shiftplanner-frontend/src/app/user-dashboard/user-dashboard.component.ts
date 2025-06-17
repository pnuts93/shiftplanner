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
import { UserService } from '../user.service';
import { Observable, of } from 'rxjs';

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
  shiftOptions: ShiftOption[] = [];
  shiftAssignments$: Observable<Record<number, Record<string, number>>> = of(
    {}
  );
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
    private authService: AuthService,
    private userService: UserService
  ) {
    this.translate.addLangs(['de', 'en']);
    this.translate.setTranslation('en', TranslationEN);
    this.translate.setTranslation('de', TranslationDE);
    this.translate.setDefaultLang(environment.defaultLocale);
    this.authService.user$.subscribe((user) => {
      if (user) {
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
    this.userService.getUsers().subscribe((users) => (this.users = users));
    this.shiftOptions = this.shiftService.getShiftOptions();
    this.shiftAssignments$ = this.shiftService.getAssignments(year, month);
  }

  generateDaysInMonth(year: number, month: number): string[] {
    month++; // Adjust month to 1-based index
    const days = new Date(year, month, 0).getDate();
    return Array.from({ length: days }, (_, i) => `${year}-${month}-${i + 1}`);
  }

  onShiftUpdate(assignment: Assignment) {
    this.shiftService.updateAssignment(assignment);
  }
}
