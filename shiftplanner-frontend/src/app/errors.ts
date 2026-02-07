export class EmailNotConfirmedError extends Error {
  constructor() {
    super('Email not confirmed');
    this.name = 'EmailNotConfirmedError';
  }
}

export class ConflictError extends Error {
  constructor() {
    super('Resource could not be updated/created due to a conflict');
    this.name = 'ConflictError';
  }
}

export class LockedError extends Error {
  constructor() {
    super('Resource is locked');
    this.name = 'LockedError';
  }
}
