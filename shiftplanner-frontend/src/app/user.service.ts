import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, ReplaySubject } from 'rxjs';
import { ApprovedUser, User, UserProfile } from './models';
import { environment } from '../environments/environment';
import { getCredentialsHeader } from './utils';

@Injectable({
  providedIn: 'root',
})
export class UserService {
  users = new ReplaySubject<User[]>(1);
  approvedUsers = new BehaviorSubject<ApprovedUser[]>([]);

  constructor() {
    this.fetchUsers();
  }

  getApprovedUsers(): Observable<ApprovedUser[]> {
    if (this.approvedUsers.value.length === 0) {
      this.fetchApprovedUsers();
    }
    return this.approvedUsers.asObservable();
  }

  async fetchApprovedUsers(): Promise<void> {
    return fetch(`${environment.hostname}/api/user/approved-users.php`, {
      method: 'GET',
      credentials: getCredentialsHeader(),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Failed to fetch approved users');
        }
        return response.json();
      })
      .then((data: ApprovedUser[]) => {
        this.approvedUsers.next(data);
      });
  }

  async addApprovedUser(user: ApprovedUser): Promise<void> {
    return fetch(`${environment.hostname}/api/user/approved-users.php`, {
      method: 'POST',
      credentials: getCredentialsHeader(),
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': `${localStorage.getItem('token')}`,
      },
      body: JSON.stringify(user),
    }).then((response) => {
      if (!response.ok) {
        throw new Error('Failed to add approved user');
      }
      this.approvedUsers.next([...this.approvedUsers.value, user]);
    });
  }

  async updateApprovedUser(user: ApprovedUser): Promise<void> {
    return fetch(`${environment.hostname}/api/user/approved-users.php`, {
      method: 'PUT',
      credentials: getCredentialsHeader(),
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': `${localStorage.getItem('token')}`,
      },
      body: JSON.stringify(user),
    }).then((response) => {
      if (!response.ok) {
        throw new Error('Failed to update approved user');
      }
    });
  }

  async removeApprovedUser(deletedUser: ApprovedUser): Promise<void> {
    return fetch(`${environment.hostname}/api/user/approved-users.php`, {
      method: 'DELETE',
      credentials: getCredentialsHeader(),
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': `${localStorage.getItem('token')}`,
      },
      body: JSON.stringify({ email: deletedUser.email }),
    }).then((response) => {
      if (!response.ok) {
        throw new Error('Failed to remove approved user');
      }
      const updatedUsers = this.approvedUsers.value.filter(
        (user) => user.email !== deletedUser.email
      );
      this.approvedUsers.next(updatedUsers);
    });
  }

  getUsers(force: boolean = false): Observable<User[]> {
    if (force) {
      this.fetchUsers();
    }
    return this.users.asObservable();
  }

  async fetchUsers(): Promise<void> {
    return fetch(`${environment.hostname}/api/user/users.php`, {
      method: 'GET',
      credentials: getCredentialsHeader(),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Failed to fetch users');
        }
        return response.json();
      })
      .then((data: User[]) => {
        this.users.next(data);
      });
  }

  async updateUser(updatedUser: UserProfile): Promise<void> {
    return fetch(`${environment.hostname}/api/user/users.php`, {
      method: 'PUT',
      credentials: getCredentialsHeader(),
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': `${localStorage.getItem('token')}`,
      },
      body: JSON.stringify(updatedUser),
    }).then((response) => {
      if (!response.ok) {
        throw new Error('Failed to update profile');
      }
      return response.json();
    });
  }
}
