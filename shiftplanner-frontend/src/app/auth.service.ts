import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { User } from './models';
import { environment } from '../environments/environment';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private userSubject = new BehaviorSubject<User | null>(null);
  user$ = this.userSubject.asObservable();

  login(user: User) {
    this.userSubject.next(user);
  }

  logout() {
    this.userSubject.next(null);
    localStorage.removeItem('token');
  }

  async getRole(): Promise<string | null> {
    return this.getUser()
      .then((value) => {
        return value?.role ?? null;
      })
      .catch((_err) => {
        return null;
      });
  }

  async getUser(): Promise<User | null> {
    if (!this.userSubject.getValue()) {
      return this.fetchUser().then((user) => {
        this.userSubject.next(user);
        return user;
      });
    }
    return this.userSubject.getValue();
  }

  async fetchUser(): Promise<User | null> {
    const token = localStorage.getItem('token');
    if (!token) {
      return null;
    }
    return fetch(environment.hostname + '/api/auth/auth.php', {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
      .then((response) => {
        if (!response.ok) {
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
          employmentDate: new Date(data.user.employmentDate),
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

  isAuthenticated(): boolean {
    return this.getRole() !== null;
  }

  async executeLogin(email: string, password: string): Promise<void> {
    return fetch(environment.hostname + '/api/auth/login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email, password }),
    })
      .then((response) => {
        if (!response.ok) {
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
          employmentDate: new Date(data.user.employmentDate),
          hasSpecialization: data.user.hasSpecialization,
          locale: data.user.locale || environment.defaultLocale,
          role: data.user.role,
        };
        this.login(user);
      })
      .catch((error) => {
        throw error;
      });
  }

  setUserLocale(locale: string) {
    this.getUser().then((value) => {
      if (!value) {
        return;
      }
      value.locale = locale;
      this.userSubject.next(value);
    });
  }
}
