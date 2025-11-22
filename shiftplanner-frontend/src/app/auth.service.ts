import { Injectable } from '@angular/core';
import { Observable, ReplaySubject } from 'rxjs';
import { User } from './models';
import { environment } from '../environments/environment';
import { EmailNotConfirmedError } from './errors';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private userSubject = new ReplaySubject<User | null>(1);
  user$ = this.userSubject.asObservable();
  currentUser: User | null = null;

  constructor() {
    this.fetchUser()
      .then((user) => {
        this.userSubject.next(user);
      })
      .catch((_) => {
        console.warn('Error fetching user');
      });
    this.user$.subscribe((user) => {
      this.currentUser = user;
    });
  }

  login(user: User) {
    this.userSubject.next(user);
  }

  logout() {
    this.userSubject.next(null);
    localStorage.removeItem('token');
  }

  getUser(): Observable<User | null> {
    return this.userSubject.asObservable();
  }

  async fetchUser(): Promise<User | null> {
    const token = localStorage.getItem('token');
    if (!token) {
      return null;
    }
    return fetch(`${environment.hostname}/api/auth/auth.php`, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
      .then((response) => {
        if (!response.ok) {
          if (response.status === 401) {
            this.logout();
            return null;
          }
          throw new Error('Login failed');
        }
        return response.json();
      })
      .then((data) => {
        const user: User = {
          id: data.user.id,
          email: data.user.email,
          fname: data.user.fname,
          lname: data.user.lname,
          employmentDate: data.user.employmentDate,
          hasSpecialization: data.user.hasSpecialization,
          locale: data.user.locale || environment.defaultLocale,
          role: data.user.role,
        };
        return user;
      })
      .catch((error) => {
        throw error;
      });
  }

  async executeLogin(email: string, password: string): Promise<void> {
    return fetch(`${environment.hostname}/api/auth/login.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email, password }),
    })
      .then((response) => {
        if (!response.ok) {
          if (response.status === 403) {
            throw new EmailNotConfirmedError();
          }
          throw new Error('Login failed');
        }
        return response.json();
      })
      .then((data) => {
        localStorage.setItem('token', data.token);
        const user: User = {
          id: data.user.id,
          email: data.user.email,
          fname: data.user.fname,
          lname: data.user.lname,
          employmentDate: data.user.employmentDate,
          hasSpecialization: data.user.hasSpecialization,
          locale: data.user.locale || environment.defaultLocale,
          role: data.user.role,
        };
        this.login(user);
      });
  }
}
