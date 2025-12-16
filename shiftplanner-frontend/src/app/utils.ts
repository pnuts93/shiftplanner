import { environment } from '../environments/environment';

export function getCredentialsHeader(): 'include' | 'same-origin' {
  return environment.dev ? 'include' : 'same-origin';
}
