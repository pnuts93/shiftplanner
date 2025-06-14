import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
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
import TranslationEN from '../../../public/i18n/en.json';
import TranslationDE from '../../../public/i18n/de.json';
import { AuthService } from '../auth.service';
import { User } from '../models';
import { environment } from '../../environments/environment';

interface AllowedUser {
  email: string;
  isAdmin: boolean;
}

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
export class AdminDashboardComponent implements OnInit {
  allowedUsers: AllowedUser[] = [];
  emailForm!: FormGroup;
  fb: FormBuilder;
  user: User | null = null;

  constructor(
    private translate: TranslateService,
    private authService: AuthService
  ) {
    this.fb = inject(FormBuilder);
    this.emailForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      isAdmin: false,
    });
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

  ngOnInit(): void {
    this.allowedUsers = [
      { email: 'admin@example.com', isAdmin: true },
      { email: 'user@example.com', isAdmin: false },
    ];
  }

  addUser(): void {
    const newUser: AllowedUser = {
      email: this.emailForm.value.email.trim(),
      isAdmin: this.emailForm.value.isAdmin,
    };

    if (!this.allowedUsers.some((u) => u.email === newUser.email)) {
      this.allowedUsers.push(newUser);
      this.emailForm.reset({ isAdmin: false });
      // TODO: Save to backend
    }
  }

  removeUser(email: string): void {
    const confirmation = confirm(
      this.translate.instant('admin.warning', { email: email })
    );
    if (!confirmation) {
      return;
    }
    this.allowedUsers = this.allowedUsers.filter((u) => u.email !== email);
    // TODO: Remove from backend
  }

  toggleAdmin(user: AllowedUser): void {
    // TODO: Update backend admin status
    console.log(`${user.email} is now admin: ${user.isAdmin}`);
  }
}
