import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import {
  FormBuilder,
  FormGroup,
  FormsModule,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatDividerModule } from '@angular/material/divider';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatListModule } from '@angular/material/list';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import {
  TranslateModule,
  TranslatePipe,
  TranslateService,
} from '@ngx-translate/core';
import { AuthService } from '../auth.service';
import { ApprovedUser, User } from '../models';
import { UserService } from '../user.service';
import { Observable } from 'rxjs';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    MatButtonModule,
    MatCardModule,
    MatCheckboxModule,
    MatDividerModule,
    MatFormFieldModule,
    MatIconModule,
    MatInputModule,
    MatListModule,
    MatSlideToggleModule,
    ReactiveFormsModule,
    TranslateModule,
    TranslatePipe,
  ],
  templateUrl: './admin-dashboard.component.html',
  styleUrl: './admin-dashboard.component.css',
})
export class AdminDashboardComponent {
  allowedUsers$: Observable<ApprovedUser[]>;
  emailForm!: FormGroup;
  fb: FormBuilder;
  user: User | null = null;

  constructor(
    private translate: TranslateService,
    private authService: AuthService,
    private userService: UserService
  ) {
    this.fb = inject(FormBuilder);
    this.emailForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      isAdmin: false,
      isCounted: true,
    });
    this.allowedUsers$ = this.userService.getApprovedUsers();
    this.authService.user$.subscribe((user) => {
      if (user) {
        this.user = user;
      }
    });
  }

  addUser(): void {
    const newUser: ApprovedUser = {
      email: this.emailForm.value.email.trim(),
      isAdmin: this.emailForm.value.isAdmin,
      isCounted: this.emailForm.value.isCounted,
    };

    this.userService.addApprovedUser(newUser).then(() => {
      this.emailForm.reset();
      this.emailForm.patchValue({ isAdmin: false, isCounted: true });
    });
  }

  removeUser(deletedUser: ApprovedUser): void {
    const confirmation = confirm(
      this.translate.instant('admin.warning', { email: deletedUser.email })
    );
    if (!confirmation) {
      return;
    }
    this.userService.removeApprovedUser(deletedUser).then(() => {
      if (this.user && this.user.email === deletedUser.email) {
        this.authService.executeLogout();
      }
    });
  }

  toggleAttribute(user: ApprovedUser): void {
    this.userService.updateApprovedUser(user);
    this.userService.getUsers(true);
  }

  canBeRemoved(approvedUser: ApprovedUser): boolean {
    return (
      this.user !== null &&
      (this.user.email === approvedUser.email || !approvedUser.isAdmin)
    );
  }
}
