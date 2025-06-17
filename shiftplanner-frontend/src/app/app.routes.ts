import { Router, Routes } from '@angular/router';
import { LoginComponent } from './login/login.component';
import { UserDashboardComponent } from './user-dashboard/user-dashboard.component';
import { AuthService } from './auth.service';
import { inject } from '@angular/core';
import { AdminDashboardComponent } from './admin-dashboard/admin-dashboard.component';
import { UserProfileComponent } from './user-profile/user-profile.component';
import { map, Observable } from 'rxjs';

const roleGuard = (requiredRoles: string[]) => {
  return (): Observable<boolean> => {
    const authService = inject(AuthService);
    const router = inject(Router);
    return authService.getUser().pipe(
      map(user => {
        if (user && user.role && requiredRoles.includes(user.role)) {
          return true;
        }
        router.navigate(['/login']);
        return false;
      })
    );
  };
};

export const routes: Routes = [
  { path: 'login', component: LoginComponent },
  {
    path: '',
    component: UserDashboardComponent,
    canActivate: [roleGuard(['user', 'admin'])],
  },
  {
    path: 'profile',
    component: UserProfileComponent,
    canActivate: [roleGuard(['user', 'admin'])],
  },
  {
    path: 'admin',
    component: AdminDashboardComponent,
    canActivate: [roleGuard(['admin'])],
  },
];
