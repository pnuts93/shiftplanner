import { environment } from '../environments/environment';
import { Configuration } from './models';

export function getCredentialsHeader(): 'include' | 'same-origin' {
  return environment.dev ? 'include' : 'same-origin';
}

export function emptyConfig(): Configuration {
  return {
    shifts: [],
    experiencedYearsThreshold: 0,
    maxPeoplePerShift: 0,
    minExpertsPerShift: 0,
  };
}
