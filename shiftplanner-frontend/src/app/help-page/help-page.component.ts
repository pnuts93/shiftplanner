import { Component } from '@angular/core';
import { Configuration, ShiftOption } from '../models';
import { ConfigurationService } from '../configuration.service';
import { emptyConfig } from '../utils';
import { TranslateModule, TranslatePipe } from '@ngx-translate/core';
import { CommonModule } from '@angular/common';
import { MatListModule } from '@angular/material/list';
import { MatIconModule } from '@angular/material/icon';
import { MatTableModule } from '@angular/material/table';

@Component({
  selector: 'app-help-page',
  imports: [
    CommonModule,
    TranslateModule,
    TranslatePipe,
    MatListModule,
    MatIconModule,
    MatTableModule,
  ],
  templateUrl: './help-page.component.html',
  styleUrl: './help-page.component.css',
})
export class HelpPageComponent {
  configuration: Configuration = emptyConfig();

  constructor(configService: ConfigurationService) {
    configService.getConfiguration().subscribe((config) => {
      this.configuration = config;
    });
  }

  getIconForShift(shift_option: ShiftOption): 'work' | 'person' {
    return shift_option.isWorkingShift ? 'work' : 'person';
  }
}
