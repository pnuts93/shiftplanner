import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, ReplaySubject } from 'rxjs';
import { Assignment, ShiftOption, User } from './models';
import { environment } from '../environments/environment';
import { UserService } from './user.service';

@Injectable({
  providedIn: 'root',
})
export class ShiftService {
  /** UserId -> Date -> ShiftId */
  private assignmentRecords = new ReplaySubject<
    Record<number, Record<string, number>>
  >(1);
  private assignments: BehaviorSubject<Assignment[]> = new BehaviorSubject<
    Assignment[]
  >([]);
  private users: User[] = [];

  constructor(private userService: UserService) {
    this.userService.getUsers().subscribe((fetchedUsers) => {
      this.users = fetchedUsers;
    });
  }

  getShiftOptions(): ShiftOption[] {
    return [
      { id: 0, label: 'none' },
      { id: 1, label: 'early' },
      { id: 2, label: 'late' },
      { id: 3, label: 'night' },
      { id: 4, label: 'vacation' },
      { id: 5, label: 'training' },
    ];
  }

  getAssignments(
    year: number,
    month: number
  ): Observable<Record<number, Record<string, number>>> {
    if (this.isTargetDate(year, month)) {
      return this.assignmentRecords.asObservable();
    }
    month++; // Adjust month to 1-based index
    fetch(
      `${environment.hostname}/api/shift/assignments.php?year=${year}&month=${month}`,
      {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${localStorage.getItem('token')}`,
        },
      }
    )
      .then((response) => {
        if (!response.ok) {
          throw new Error('Failed to fetch assignments');
        }
        return response.json();
      })
      .then((data: Assignment[]) => {
        this.assignments.next(data);
        const assignments: Record<
          number,
          Record<string, number>
        > = this.assignmentsToRecord(data);
        // Initialize empty records for each user and date in the month
        const daysInMonth = new Date(year, month, 0).getDate();
        for (const user of this.users) {
          if (!assignments[user.id]) {
            assignments[user.id] = {};
          }
          for (let day = 1; day <= daysInMonth; day++) {
            const date = `${year}-${month}-${day}`;
            if (!assignments[user.id][date]) {
              assignments[user.id][date] = 0;
            }
          }
        }
        this.assignmentRecords.next(assignments);
      })
      .catch((error) => {
        console.error('Error fetching assignments:', error);
      });
    return this.assignmentRecords.asObservable();
  }

  async updateAssignment(assignment: Assignment): Promise<void> {
    fetch(`${environment.hostname}/api/shift/assignments.php`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
      },
      body: JSON.stringify(assignment),
    })
    .then((response) => {
      if (!response.ok) {
        throw new Error('Failed to update assignment');
      }
    })
  }

  assignmentsToRecord(
    assignments: Assignment[]
  ): Record<number, Record<string, number>> {
    let record: Record<number, Record<string, number>> = {};
    assignments.forEach((assignment) => {
      if (!record[assignment.userId]) {
        record[assignment.userId] = {};
      }
      let date = new Date(assignment.date);
      // Ensure date is in YYYY-MM-DD format
      let formattedDate = `${date.getFullYear()}-${date.getMonth() + 1}-${date.getDate()}`;
      record[assignment.userId][formattedDate] = assignment.shiftId;
    });
    return record;
  }

  private isTargetDate(year: number, month: number): boolean {
    if (this.assignments.getValue().length === 0) {
      return false;
    }
    const cachedAssignmentDate = this.assignments.getValue()[0].date;
    const targetDate = new Date(cachedAssignmentDate);
    return year === targetDate.getFullYear() && month === targetDate.getMonth();
  }
}
