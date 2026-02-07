import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, ReplaySubject } from 'rxjs';
import { MonthBlocker } from './models';
import { environment } from '../environments/environment';
import { getCredentialsHeader } from './utils';

@Injectable({
  providedIn: 'root',
})
export class MonthBlockerService {
  private monthBlockers: ReplaySubject<MonthBlocker[]> = new ReplaySubject<
    MonthBlocker[]
  >(1);
  private currentMonthBlockers: MonthBlocker[] = [];

  constructor() {
    this.fetchMonthBlockers();
    this.monthBlockers.subscribe((blockers) => {
      this.currentMonthBlockers = blockers;
    });
  }

  getMonthBlockers(): Observable<MonthBlocker[]> {
    return this.monthBlockers.asObservable();
  }

  async fetchMonthBlockers(): Promise<void> {
    return fetch(`${environment.hostname}/api/config/month-blockers.php`, {
      method: 'GET',
      credentials: getCredentialsHeader(),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Failed to fetch shift options');
        }
        return response.json();
      })
      .then((data) => {
        this.monthBlockers.next(data as MonthBlocker[]);
      })
      .catch((e) => {
        console.error(e);
      });
  }

  async createMonthBlocker(year: number, month: number): Promise<void> {
    return fetch(`${environment.hostname}/api/config/month-blockers.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': `${localStorage.getItem('token')}`,
      },
      body: JSON.stringify({ year, month }),
      credentials: getCredentialsHeader(),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Failed to create month blocker');
        }
        this.monthBlockers.next([
          ...this.currentMonthBlockers,
          { year, month },
        ]);
      })
      .catch((e) => {
        console.error(e);
      });
  }

  async deleteMonthBlocker(year: number, month: number): Promise<void> {
    return fetch(`${environment.hostname}/api/config/month-blockers.php`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': `${localStorage.getItem('token')}`,
      },
      body: JSON.stringify({ year, month }),
      credentials: getCredentialsHeader(),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Failed to delete month blocker');
        }
        this.monthBlockers.next(
          this.currentMonthBlockers.filter(
            (mb) => !(mb.year === year && mb.month === month),
          ),
        );
      })
      .catch((e) => {
        console.error(e);
      });
  }
}
