import { Injectable } from '@angular/core';
import { Observable, ReplaySubject } from 'rxjs';
import { Configuration } from './models';
import { environment } from '../environments/environment';
import { getCredentialsHeader } from './utils';

@Injectable({
  providedIn: 'root',
})
export class ConfigurationService {
  private configuration: ReplaySubject<Configuration> =
    new ReplaySubject<Configuration>(1);

  constructor() {
    this.fetchConfiguration();
  }

  getConfiguration(): Observable<Configuration> {
    return this.configuration.asObservable();
  }

  async fetchConfiguration() {
    fetch(`${environment.hostname}/api/config/config.php`, {
      method: 'GET',
      credentials: getCredentialsHeader(),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Failed to fetch configuration');
        }
        return response.json();
      })
      .then((data) => {
        this.configuration.next(data as Configuration);
      })
      .catch((e) => {
        console.error(e);
      });
  }
}
