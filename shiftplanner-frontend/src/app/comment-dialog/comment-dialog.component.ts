import { Component, inject, model } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import {
  MAT_DIALOG_DATA,
  MatDialogModule,
  MatDialogRef,
} from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { TranslateModule } from '@ngx-translate/core';
import { CommentDialogData } from '../models';

@Component({
  selector: 'app-comment-dialog',
  imports: [
    FormsModule,
    MatButtonModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    TranslateModule,
  ],
  templateUrl: './comment-dialog.component.html',
  styleUrl: './comment-dialog.component.css',
})
export class CommentDialogComponent {
  readonly dialogRef = inject(MatDialogRef<CommentDialogComponent>);
  readonly data = inject<CommentDialogData>(MAT_DIALOG_DATA);

  onNoClick(): void {
    this.dialogRef.close();
  }

  onDeleteClick(): void {
    this.dialogRef.close('');
  }
}
