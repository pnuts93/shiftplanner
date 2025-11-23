
import { Component, inject, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { TranslatePipe } from '@ngx-translate/core';
import { RequestState } from '../models';
import { environment } from '../../environments/environment';
import { ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-password-forgotten',
  imports: [
    MatButtonModule,
    MatCardModule,
    MatInputModule,
    MatProgressSpinnerModule,
    ReactiveFormsModule,
    TranslatePipe
],
  templateUrl: './recovery.component.html',
  styleUrl: './recovery.component.css',
})
export class RecoveryComponent implements OnInit {
  resetRequestState: RequestState = 'idle';
  type: 'password_reset' | 'email_confirmation' = 'password_reset';
  private formBuilder = inject(FormBuilder);
  passwordForgotForm = this.formBuilder.group({
    email: ['', [Validators.required, Validators.email]],
  });

  constructor(private route: ActivatedRoute) {}

  ngOnInit() {
    // route must contain a query parameter 'type' to determine the type of request
    this.route.queryParams.subscribe((params) => {
      if (!params['type'] || (params['type'] !== 'password_reset' && params['type'] !== 'email_confirmation')) {
        throw new Error('Invalid or missing type parameter');
      }
      this.type = params['type'];
    });
  }

  onForgotPassword() {
    if (this.passwordForgotForm.valid) {
      this.resetRequestState = 'loading';
      const email = this.passwordForgotForm.value.email;
      fetch(`${environment.hostname}/api/auth/otp.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email: email, token_type: this.type }),
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error('Request failed');
          }
          this.resetRequestState = 'success';
        })
        .catch((_) => {
          this.resetRequestState = 'error';
        });
    }
  }
}
