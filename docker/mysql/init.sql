-- Separate database for `php artisan test` (CODING_CONTRACT C8.2).
CREATE DATABASE IF NOT EXISTS exam_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON exam_testing.* TO 'exam'@'%';
FLUSH PRIVILEGES;
