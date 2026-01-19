import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { Configuration, ShiftOption } from './models';
import { environment } from '../environments/environment';
import { emptyConfig, getCredentialsHeader } from './utils';

@Injectable({
  providedIn: 'root',
})
export class ConfigurationService {
  private configuration: BehaviorSubject<Configuration> =
    new BehaviorSubject<Configuration>(emptyConfig());

  getConfiguration(): Observable<Configuration> {
    if (this.configuration.getValue().shifts.length > 0) {
      return this.configuration.asObservable();
    }
    fetch(`${environment.hostname}/api/config/config.php`, {
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
        this.configuration.next(data as Configuration);
      })
      .catch((e) => {
        console.error(e);
      });
    return this.configuration.asObservable();
  }
}
