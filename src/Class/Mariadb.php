<?php

class Mariadb extends PDO
{
    public function __construct()
    {
        $dsn = "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_BASE') . ";charset=utf8mb4";

        $options = [
            // Disable emulation mode for "real" prepared statements
            PDO::ATTR_EMULATE_PREPARES => false,
            // Disable errors in the form of exceptions
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Make the default fetch be an associative array
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            return parent::__construct($dsn, getenv('DB_USER'), getenv('DB_PASS'), $options);
        } catch (Exception $e) {
            error_log($e->getMessage());
            exit('Something bad happened: check logs');
        }
    }
}
