-- Simulates the legacy tax platform's user table that already lives in RDS.
-- WordPress does NOT create this; it pre-exists with real users.
CREATE TABLE IF NOT EXISTS clients (
  id            SERIAL PRIMARY KEY,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(190) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seeded users (bcrypt hashes verify these plaintext passwords):
--   maria@taxplatform.com -> TaxPass123!
--   joao@taxplatform.com  -> Secret456!
INSERT INTO clients (email, password_hash, full_name) VALUES
  ('maria@taxplatform.com', '$2y$10$yOhPwkzQnlHoTjutSjkRUOR9cV8A3mXDMrYSbILFbkyQuHBVHwqqu', 'Maria Silva'),
  ('joao@taxplatform.com',  '$2y$10$m.pHO0qJCv4pyT.8Q4V2Ie2dsSfZRBpL0w5t6IwQZDGxPQY4GyCf6', 'Joao Souza')
ON CONFLICT (email) DO NOTHING;
