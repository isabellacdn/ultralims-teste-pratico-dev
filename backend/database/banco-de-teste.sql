CREATE DATABASE IF NOT EXISTS ultralims_teste
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON ultralims_teste.* TO 'ultralims'@'%';
FLUSH PRIVILEGES;

USE ultralims_teste;

SOURCE /docker-entrypoint-initdb.d/01-schema.sql;
