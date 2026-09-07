-- ITCS333 Campus Marketplace — seed data (portable).
-- Password for all seeded users is 'password' (bcrypt hash below).

INSERT INTO categories (id, name) VALUES
  (1, 'Stationery'),
  (2, 'Electronics'),
  (3, 'Books');

INSERT INTO products (id, category_id, name, price, stock) VALUES
  (1, 1, 'Notebook', 3.50, 50),
  (2, 1, 'Pen', 1.25, 200),
  (3, 2, 'USB Drive 32GB', 7.00, 40),
  (4, 3, 'Web Development Book', 30.00, 10),
  (5, 2, 'Headphones', 19.90, 25);

-- bcrypt hash of 'password'
INSERT INTO users (id, username, password_hash, is_admin) VALUES
  (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
  (2, 'student', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0);

INSERT INTO admin_log (id, admin_id, action, created_at) VALUES
  (1, 1, 'seed', '2026-01-01T00:00:00Z');
