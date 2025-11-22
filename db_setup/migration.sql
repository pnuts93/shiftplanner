CREATE TABLE approved_users (
    email TEXT PRIMARY KEY,
    is_admin BOOLEAN DEFAULT FALSE
);

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email TEXT NOT NULL UNIQUE REFERENCES approved_users(email) ON DELETE CASCADE,
    password TEXT NOT NULL,
    is_email_confirmed BOOLEAN DEFAULT FALSE NOT NULL,
    fname TEXT NOT NULL,
    lname TEXT NOT NULL,
    employment_date DATE NOT NULL,
    has_specialization BOOLEAN DEFAULT FALSE NOT NULL,
    locale TEXT NOT NULL DEFAULT 'en'
);

CREATE TABLE assignments (
  user_id INTEGER NOT NULL,
  date DATE NOT NULL,
  shift_id SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (user_id, date),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE one_time_tokens (
  user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
  token TEXT UNIQUE,
  expires_at TIMESTAMP,
  token_type TEXT NOT NULL CHECK (token_type IN ('email_confirmation', 'password_reset'))
);