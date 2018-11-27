CREATE TABLE `categories` ( `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, `parent_id` INTEGER, `name` TEXT, `updated_at` TEXT, `created_at` TEXT, `deleted_at` TEXT DEFAULT NULL );

INSERT INTO categories (id, parent_id, name, updated_at, created_at, deleted_at) VALUES (1, null, 'root', '2018-11-22 21:11:28', '2018-11-22 21:11:28', null);
INSERT INTO categories (id, parent_id, name, updated_at, created_at, deleted_at) VALUES (2, 1, 'category 1', '2018-11-22 21:11:28', '2018-11-22 21:11:28', null);
INSERT INTO categories (id, parent_id, name, updated_at, created_at, deleted_at) VALUES (3, 1, 'category 2', '2018-11-22 21:11:28', '2018-11-22 21:11:28', null);
INSERT INTO categories (id, parent_id, name, updated_at, created_at, deleted_at) VALUES (4, 1, 'category 3', '2018-11-22 21:11:28', '2018-11-22 21:11:28', null);
INSERT INTO categories (id, parent_id, name, updated_at, created_at, deleted_at) VALUES (5, 2, 'sub category 1-1', '2018-11-22 21:11:28', '2018-11-22 21:11:28', null);
INSERT INTO categories (id, parent_id, name, updated_at, created_at, deleted_at) VALUES (6, 2, 'sub category 1-2', '2018-11-22 21:11:28', '2018-11-22 21:11:28', null);
INSERT INTO categories (id, parent_id, name, updated_at, created_at, deleted_at) VALUES (7, 2, 'sub category 1-3', '2018-11-22 21:11:28', '2018-11-22 21:11:28', null);
INSERT INTO categories (id, parent_id, name, updated_at, created_at, deleted_at) VALUES (8, 3, 'sub category 1-1', '2018-11-22 21:11:28', '2018-11-22 21:11:28', null);
INSERT INTO categories (id, parent_id, name, updated_at, created_at, deleted_at) VALUES (9, 3, 'sub category 2-2', '2018-11-22 21:11:28', '2018-11-22 21:11:28', null);
INSERT INTO categories (id, parent_id, name, updated_at, created_at, deleted_at) VALUES (10, 3, 'sub category 2-3', '2018-11-22 21:11:28', '2018-11-22 21:11:28', null);
INSERT INTO categories (id, parent_id, name, updated_at, created_at, deleted_at) VALUES (11, 4, 'sub category 3-1', '2018-11-22 21:11:28', '2018-11-22 21:11:28', null);
INSERT INTO categories (id, parent_id, name, updated_at, created_at, deleted_at) VALUES (12, 4, 'sub category 3-2', '2018-11-22 21:11:28', '2018-11-22 21:11:28', null);
INSERT INTO categories (id, parent_id, name, updated_at, created_at, deleted_at) VALUES (13, 4, 'sub category 3-3', '2018-11-22 21:11:28', '2018-11-22 21:11:28', null);
