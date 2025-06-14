import { Component, inject, OnInit } from '@angular/core';
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
import TranslationEN from '../../../public/i18n/en.json';
import TranslationDE from '../../../public/i18n/de.json';
import { MatSelectModule } from '@angular/material/select';
import { AuthService } from '../auth.service';
import { User } from '../models';
import { CommonModule } from '@angular/common';
import { environment } from '../../environments/environment';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-user-profile',
  standalone: true,
  imports: [
    CommonModule,
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
export class UserProfileComponent implements OnInit {
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

  async ngOnInit(): Promise<void> {
    const user = await this.authService.getUser();
    if (!user) {
      return;
    }

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

  onSubmit(): void {
    if (this.profileForm.valid) {
      const updatedProfile = this.profileForm.value;
      fetch(environment.hostname + '/api/profile/update.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${localStorage.getItem('token')}`,
        },
        body: JSON.stringify({
          ...updatedProfile,
          employmentDate: updatedProfile.employmentDate.toISOString(),
        }),
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error('Failed to update profile');
          }
          return response.json();
        })
        .then((data) => {
          this.authService.login(data.user);
          this.profileForm.markAsPristine();
        })
        .catch((error) => {
          console.error('Error updating profile:', error);
        });
    }
  }

  setLocale(locale: string): void {
    this.authService.setUserLocale(locale);
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
