CREATE TABLE approved_users (
    email TEXT PRIMARY KEY,
    is_admin BOOLEAN DEFAULT FALSE
);

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email TEXT NOT NULL UNIQUE REFERENCES approved_users(email),
    password TEXT NOT NULL,
    fname TEXT NOT NULL,
    lname TEXT NOT NULL,
    employment_date DATE NOT NULL,
    has_specialization BOOLEAN DEFAULT FALSE NOT NULL,
    locale TEXT NOT NULL DEFAULT 'en',
);
