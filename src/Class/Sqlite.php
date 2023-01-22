<?php

class Sqlite extends PDO
{
    public function __construct()
    {
        $dsn = 'sqlite:' . APPLICATION_PATH . '/../data/database.sqlite3';

        try {
            parent::__construct($dsn);
            $this->setAttribute(PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION);
            // Create table messages
            $this->exec("CREATE TABLE IF NOT EXISTS `user` (
                    id INTEGER PRIMARY KEY,
                    email TEXT CHECK( LENGTH(email) <= 255 ) NOT NULL DEFAULT '',
                    password TEXT CHECK( LENGTH(password) <= 255 ) NOT NULL DEFAULT '',
                    name TEXT CHECK( LENGTH(name) <= 255 ) NULL DEFAULT NULL,
                    role TEXT CHECK( LENGTH(password) <= 255 ) NOT NULL DEFAULT 'default'
                )
            ");

            return $this;
        } catch (Exception $e) {
            error_log($e->getMessage());
            exit('Something bad happened: check logs');
        }
    }
}