import { Component, inject } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { ReactiveFormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { Router, RouterModule } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { AuthService } from '../auth.service';

import { TranslateModule, TranslatePipe } from '@ngx-translate/core';
import { MatTabsModule } from '@angular/material/tabs';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { EmailNotConfirmedError } from '../errors';
import { RequestState } from '../models';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    MatCardModule,
    MatCheckboxModule,
    MatDatepickerModule,
    MatIconModule,
    MatInputModule,
    MatButtonModule,
    MatProgressSpinnerModule,
    MatTabsModule,
    ReactiveFormsModule,
    RouterModule,
    TranslateModule,
    TranslatePipe
],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css',
})
export class LoginComponent {
  loginRequestState: RequestState = 'idle';
  registrationRequestState: RequestState = 'idle';
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
  policyUrl = `${environment.hostname}/app/policy.php`;

  constructor(private authService: AuthService, private router: Router) {}

  onLogin() {
    if (this.loginForm.invalid) return;

    const { email, password } = this.loginForm.value;
    if (!email || !password) {
      return;
    }
    this.loginRequestState = 'loading';
    this.authService
      .executeLogin(email, password)
      .then(() => {
        this.router.navigate(['']);
      })
      .catch((error) => {
        if (error instanceof EmailNotConfirmedError) {
          this.router.navigate(['/recovery'], {
            queryParams: { type: 'email_confirmation' },
          });
        }
        this.loginRequestState = 'error';
      });
  }

  onRegister(): void {
    if (this.registerForm.valid) {
      const registerData = this.registerForm.value;
      this.registrationRequestState = 'loading';
      fetch(`${environment.hostname}/api/user/users.php`, {
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
          this.registrationRequestState = 'success';
        })
        .catch((_) => {
          this.registrationRequestState = 'error';
        });
    }
  }
}
