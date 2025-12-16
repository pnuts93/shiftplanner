import { ApplicationConfig, importProvidersFrom } from '@angular/core';
import { provideRouter } from '@angular/router';
import { provideTranslateService } from '@ngx-translate/core';
import { routes } from './app.routes';
import { MAT_DATE_LOCALE, MatNativeDateModule } from '@angular/material/core';
import { environment } from '../environments/environment';

export const appConfig: ApplicationConfig = {
  providers: [
    provideRouter(routes),
    provideTranslateService({
      defaultLanguage: environment.defaultLocale,
    }),
    importProvidersFrom(MatNativeDateModule),
    { provide: MAT_DATE_LOCALE, useValue: environment.defaultLocale },
  ],
};
