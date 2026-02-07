import { environment } from '../environments/environment';
import { ConflictError, LockedError } from './errors';
import { Configuration } from './models';

export function getCredentialsHeader(): 'include' | 'same-origin' {
  return environment.dev ? 'include' : 'same-origin';
}

export function emptyConfig(): Configuration {
  return {
    shifts: [],
    experiencedYearsThreshold: 0,
    maxMonthOffset: 0,
  };
}

export function handleFetchError(status: number): never {
  switch (status) {
    case 400:
      throw new Error('Invalid request data');
    case 403:
      throw new Error('Forbidden');
    case 409:
      throw new ConflictError();
    case 423:
      throw new LockedError();
    default:
      throw new Error('An unexpected error occurred');
  }
}
