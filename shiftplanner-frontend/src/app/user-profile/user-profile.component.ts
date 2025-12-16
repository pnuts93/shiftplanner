import { Component, inject } from '@angular/core';
import {
  AbstractControl,
  FormBuilder,
  FormGroup,
  FormsModule,
  ReactiveFormsModule,
  ValidationErrors,
  Validators,
} from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import {
  TranslateModule,
  TranslatePipe,
  TranslateService,
} from '@ngx-translate/core';
import { MatSelectModule } from '@angular/material/select';
import { AuthService } from '../auth.service';
import { User, UserProfile } from '../models';

import { environment } from '../../environments/environment';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatIconModule } from '@angular/material/icon';
import { UserService } from '../user.service';

@Component({
  selector: 'app-user-profile',
  standalone: true,
  imports: [
    FormsModule,
    MatButtonModule,
    MatCardModule,
    MatCheckboxModule,
    MatDatepickerModule,
    MatFormFieldModule,
    MatIconModule,
    MatInputModule,
    MatSelectModule,
    ReactiveFormsModule,
    TranslateModule,
    TranslatePipe,
  ],
  templateUrl: './user-profile.component.html',
  styleUrl: './user-profile.component.css',
})
export class UserProfileComponent {
  fb = inject(FormBuilder);
  hidePassword: boolean[] = [true, true, true];
  profileForm: FormGroup = this.fb.group({
    fname: ['', Validators.required],
    lname: ['', Validators.required],
    oldPassword: [''],
    newPassword: [''],
    confirmPassword: [''],
    employmentDate: [new Date(), Validators.required],
    hasSpecialization: false,
    locale: [environment.defaultLocale, Validators.required],
  });
  email: string = ''; // set this from the auth state
  maxDate: Date = new Date();
  availableLanguages = [
    { code: 'en', label: 'English' },
    { code: 'de', label: 'Deutsch' },
  ];
  user: User | null = null;

  constructor(
    private translate: TranslateService,
    private authService: AuthService,
    private userService: UserService
  ) {
    this.authService.user$.subscribe((user) => {
      if (user) {
        this.user = user;
        this.translate.use(user.locale);

        this.email = user.email;

        this.profileForm = this.fb.group(
          {
            fname: [user.fname, Validators.required],
            lname: [user.lname, Validators.required],
            oldPassword: [''],
            newPassword: [''],
            confirmPassword: [''],
            employmentDate: [user.employmentDate, Validators.required],
            hasSpecialization: [user.hasSpecialization, Validators.required],
            locale: [user.locale, Validators.required],
          },
          { validators: [this.oldPasswordValidator, this.newPasswordValidator] }
        );
      }
    });
  }

  onSubmit(): void {
    if (!this.profileForm.valid) {
      return;
    }
    const updatedProfile = this.profileForm.value;
    let date = new Date(updatedProfile.employmentDate);
    let utc = new Date(date.getFullYear(), date.getMonth(), date.getDate() + 1);
    updatedProfile.employmentDate = utc.toISOString().split('T')[0];
    let userProfile: UserProfile = {
      email: this.email,
      fname: updatedProfile.fname,
      lname: updatedProfile.lname,
      employmentDate: updatedProfile.employmentDate,
      hasSpecialization: updatedProfile.hasSpecialization,
      locale: updatedProfile.locale,
      oldPassword: updatedProfile.oldPassword,
      newPassword: updatedProfile.newPassword,
    };
    this.userService
      .updateUser(userProfile)
      .then(() => {
        const updatedUser: User = {
          ...this.user!,
          fname: updatedProfile.fname,
          lname: updatedProfile.lname,
          employmentDate: updatedProfile.employmentDate,
          hasSpecialization: updatedProfile.hasSpecialization,
          locale: updatedProfile.locale,
        };
        this.authService.login(updatedUser);
        this.profileForm.markAsPristine();
      })
      .catch((_) => {
        console.error('Error updating profile');
      });
    this.userService.getUsers(true);
  }

  setLocale(locale: string): void {
    this.translate.use(locale);
  }

  oldPasswordValidator(control: AbstractControl): ValidationErrors {
    const newPassword = control.get('newPassword');
    const oldPassword = control.get('oldPassword');

    if (newPassword?.value && !oldPassword?.value) {
      return { passwordMismatch: true };
    }

    return {};
  }

  newPasswordValidator(control: AbstractControl): ValidationErrors {
    const newPassword = control.get('newPassword');
    const confirmPassword = control.get('confirmPassword');

    if (newPassword?.value !== confirmPassword?.value) {
      return { passwordMismatch: true };
    }

    return {};
  }
}
