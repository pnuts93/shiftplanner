import { ChangeDetectorRef, Component, inject, NgZone } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { ReactiveFormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { Router, RouterModule } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { AuthService } from '../auth.service';
import { CommonModule } from '@angular/common';
import TranslationEN from '../../../public/i18n/en.json';
import TranslationDE from '../../../public/i18n/de.json';
import {
  TranslateModule,
  TranslatePipe,
  TranslateService,
} from '@ngx-translate/core';
import { MatTabsModule } from '@angular/material/tabs';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatCheckboxModule,
    MatDatepickerModule,
    MatIconModule,
    MatInputModule,
    MatButtonModule,
    MatTabsModule,
    ReactiveFormsModule,
    RouterModule,
    TranslateModule,
    TranslatePipe,
  ],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css',
})
export class LoginComponent {
  loading = false;
  hidePassword = true;
  private formBuilder = inject(FormBuilder);
  loginForm = this.formBuilder.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });
  registerForm = this.formBuilder.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
    fname: ['', Validators.required],
    lname: ['', Validators.required],
    employmentDate: [new Date(), Validators.required],
    hasSpecialization: false,
  });
  maxDate = new Date();

  constructor(
    private authService: AuthService,
    private router: Router,
    private translate: TranslateService
  ) {
    this.translate.addLangs(['de', 'en']);
    this.translate.setTranslation('en', TranslationEN);
    this.translate.setTranslation('de', TranslationDE);
    this.translate.setDefaultLang(environment.defaultLocale);
  }

  onLogin() {
    if (this.loginForm.invalid) return;
    this.loading = true;

    const { email, password } = this.loginForm.value;
    if (!email || !password) {
      this.loading = false;
      return;
    }
    this.authService
      .executeLogin(email, password)
      .then(() => {
        this.loading = false;
        this.router.navigate(['']);
      })
      .catch((_err) => {
        this.loading = false;
      });
  }

  onRegister(): void {
    if (this.registerForm.valid) {
      const registerData = this.registerForm.value;
      fetch('http://localhost:8000/api/auth/register.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email: registerData.email,
          password: registerData.password,
          fname: registerData.fname,
          lname: registerData.lname,
          employmentDate: registerData.employmentDate?.toISOString(),
          hasSpecialization: registerData.hasSpecialization,
        }),
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error('Registration failed: ' + response.statusText);
          }
          this.router.navigate(['/login']);
        })
        .catch((error) => {
          console.error('Registration failed:', error);
        });
    }
  }
}
