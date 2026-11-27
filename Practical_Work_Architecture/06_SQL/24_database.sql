CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);
SELECT name
FROM sqlite_master
WHERE type = 'table';
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    login TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    role_id INTEGER NOT NULL,
    password_hash TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
SELECT name
FROM sqlite_master
WHERE type = 'table'
AND name IN ('roles', 'users');
SELECT name
FROM sqlite_master
WHERE type = 'table'
AND name IN ('roles', 'users');
CREATE TABLE sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    session_token TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
SELECT name
FROM sqlite_master
WHERE type = 'table'
AND name = 'sessions';
CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT
);
SELECT name
FROM sqlite_master
WHERE type = 'table'
AND name = 'categories';
CREATE TABLE games (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    developer TEXT,
    release_year INTEGER,
    price REAL DEFAULT 0,
    rating REAL DEFAULT 0,
    category_id INTEGER,
    description TEXT,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
SELECT name
FROM sqlite_master
WHERE type = 'table'
AND name = 'games';
CREATE TABLE preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);
CREATE TABLE events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    game_id INTEGER,
    event_type TEXT NOT NULL,
    weight REAL DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (game_id) REFERENCES games(id)
);
CREATE TABLE user_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    preference_id INTEGER NOT NULL,
    score REAL DEFAULT 0,
    UNIQUE(user_id, preference_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (preference_id) REFERENCES preferences(id)
);
CREATE TABLE groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT
);
CREATE TABLE user_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    group_id INTEGER NOT NULL,
    UNIQUE(user_id, group_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (group_id) REFERENCES groups(id)
);
CREATE TABLE group_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    preference_id INTEGER NOT NULL,
    score REAL DEFAULT 0,
    UNIQUE(group_id, preference_id),
    FOREIGN KEY (group_id) REFERENCES groups(id),
    FOREIGN KEY (preference_id) REFERENCES preferences(id)
);
SELECT name
FROM sqlite_master
WHERE type = 'table'
AND name = 'group_preferences';
CREATE TABLE group_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    score REAL DEFAULT 0,
    UNIQUE(group_id, category_id),
    FOREIGN KEY (group_id) REFERENCES groups(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
CREATE TABLE game_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    game_id INTEGER NOT NULL,
    preference_id INTEGER NOT NULL,
    score REAL DEFAULT 0,
    UNIQUE(game_id, preference_id),
    FOREIGN KEY (game_id) REFERENCES games(id),
    FOREIGN KEY (preference_id) REFERENCES preferences(id)
);
CREATE TABLE game_relations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    game_id INTEGER NOT NULL,
    related_game_id INTEGER NOT NULL,
    score REAL DEFAULT 0,
    UNIQUE(game_id, related_game_id),
    FOREIGN KEY (game_id) REFERENCES games(id),
    FOREIGN KEY (related_game_id) REFERENCES games(id)
);
CREATE TABLE carts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE TABLE cart_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cart_id INTEGER NOT NULL,
    game_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    UNIQUE(cart_id, game_id),
    FOREIGN KEY (cart_id) REFERENCES carts(id),
    FOREIGN KEY (game_id) REFERENCES games(id)
);
CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    total_amount REAL NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    game_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    price REAL NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (game_id) REFERENCES games(id)
);
CREATE INDEX idx_games_title ON games(title);

CREATE INDEX idx_games_category ON games(category_id);

CREATE INDEX idx_events_user ON events(user_id);

CREATE INDEX idx_events_game ON events(game_id);

CREATE INDEX idx_orders_user ON orders(user_id);

CREATE INDEX idx_sessions_user ON sessions(user_id);
SELECT COUNT(*) AS количество_таблиц
FROM sqlite_master
WHERE type = 'table'
AND name NOT LIKE 'sqlite_%';
SELECT name
FROM sqlite_master
WHERE type = 'index'
AND name LIKE 'idx_%';