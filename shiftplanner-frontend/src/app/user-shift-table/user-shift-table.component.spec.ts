import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UserShiftTableComponent } from './user-shift-table.component';

describe('UserShiftTableComponent', () => {
  let component: UserShiftTableComponent;
  let fixture: ComponentFixture<UserShiftTableComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [UserShiftTableComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(UserShiftTableComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
