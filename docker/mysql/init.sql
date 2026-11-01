-- Separate database for the test suite so `make test` never touches dev data.
CREATE DATABASE IF NOT EXISTS grammatica_test;
GRANT ALL PRIVILEGES ON grammatica_test.* TO 'grammatica'@'%';
