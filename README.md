# RESTFUL PHP API Backend

This is a simple Restfull PHP API Backend.

It's build with 11 PHP files.

## It provides :

 - Restful PHP Service 
 - Jwt authentication 
 - Password hash sha256
 - Cors support
 - Authentication controller 
 - A simple CRUD User controller for start working with Restful Api
 - Database Object model
 - Full Class / Controller / Service / Model autoload
 - Cache API map
 - PHPDoc reflexion 
 - Dotenv support
 - Mariadb PDO support
 - Sqlite PDO support
 - Postman test collection

## Database

```sql
CREATE TABLE scan.`user` (
    id bigint(20) auto_increment NOT NULL,
    email varchar(255) NOT NULL,
    password varchar(255) NOT NULL,
    name varchar(255) NULL,
    `role` ENUM('admin','default') DEFAULT 'default' NOT NULL,
    CONSTRAINT user_PK PRIMARY KEY (id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8
COLLATE=utf8_general_ci;
```

