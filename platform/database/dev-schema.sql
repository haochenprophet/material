-- 开发用最小表（SQLite/MySQL 均可；不是 45 表生产 schema）
CREATE TABLE IF NOT EXISTS materials (
  id          VARCHAR(32) PRIMARY KEY,
  title       VARCHAR(255) NOT NULL,
  spec        VARCHAR(255) NOT NULL,
  price       VARCHAR(32)  NOT NULL,
  unit        VARCHAR(16)  NOT NULL DEFAULT '个',
  stock       VARCHAR(64)  NOT NULL,
  location    VARCHAR(64)  NOT NULL DEFAULT '',
  badge       VARCHAR(64)  NOT NULL DEFAULT '',
  badge_kind  VARCHAR(16)  NOT NULL DEFAULT '',
  thumb       VARCHAR(128) NOT NULL DEFAULT '',
  thumb_class VARCHAR(32)  NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS orders (
  id          VARCHAR(64) PRIMARY KEY,
  status      VARCHAR(32) NOT NULL,
  goods       REAL NOT NULL DEFAULT 0,
  subtotal    REAL NOT NULL DEFAULT 0,
  total       REAL NOT NULL DEFAULT 0,
  created_at  VARCHAR(40) NOT NULL,
  updated_at  VARCHAR(40) NOT NULL
);

CREATE TABLE IF NOT EXISTS order_lines (
  order_id   VARCHAR(64) NOT NULL,
  line_no    INTEGER NOT NULL,
  mpn        VARCHAR(255) NOT NULL DEFAULT '',
  qty        REAL NOT NULL,
  unit       REAL NOT NULL,
  PRIMARY KEY (order_id, line_no)
);

