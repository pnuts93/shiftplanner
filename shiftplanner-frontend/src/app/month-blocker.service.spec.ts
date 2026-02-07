import { TestBed } from '@angular/core/testing';

import { MonthBlockerService } from './month-blocker.service';

describe('MonthBlockerService', () => {
  let service: MonthBlockerService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(MonthBlockerService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
