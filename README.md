# RESTFUL PHP API Backend with JWT Authentification

This is a simple Restfull PHP API Backend.

It's build with 11 PHP files.

## It provides

 - Restful PHP Service 
 - Jwt authentication 
 - Password hash sha256
 - Cors support
 - Authentication controller 
 - A simple CRUD User controller for start working with Restful Api
 - Database Object model
 - Full Class / Controller / Model / Service autoload
 - Cache API map
 - PHPDoc reflexion 
 - Dotenv support
 - Mariadb PDO support
 - Sqlite PDO support
 - Postman test collection

## .env file

Copy the **.env.example** to **.env** file and update what you need

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

## Tree

```bash
├── public
│   └── index.php
├── src
    ├── Class
    │   ├── Dotenv.php
    │   ├── Mariadb.php
    │   └── Sqlite.php
    ├── Controller
    │   ├── AbstractController.php
    │   ├── AuthController.php
    │   └── UserController.php
    ├── Model
    │   ├── AbstractModel.php
    │   └── UserModel.php
    └── Service
        ├── JwtService.php
        └── RestService.php
```

## How to use

### Start
The easiest way is to start a Docker php7.4-fpm container pointing on public folder.

You can use this docker stack : https://github.com/xenetis/docker-restful-php-server

Just add an admin user using the postman_collection

### Available URL

```php
src/Controller/AbstractController.php: * @url GET /

src/Controller/UserController.php:     * @url GET /user
src/Controller/UserController.php:     * @url POST /user
src/Controller/UserController.php:     * @url PUT /user/$id
src/Controller/UserController.php:     * @url DELETE /user/$id
src/Controller/UserController.php:     * @url DELETE /user/reset

src/Controller/AuthController.php:     * @url POST /auth/login
src/Controller/AuthController.php:     * @url POST /auth/register
src/Controller/AuthController.php:     * @url POST /auth/logout
src/Controller/AuthController.php:     * @url POST /auth/requestpass
src/Controller/AuthController.php:     * @url POST /auth/resetpass
src/Controller/AuthController.php:     * @url POST /auth/refresh-token
```

### API PHPDoc parameters 

In your action controller you have to provide API PHPDoc parameters.

#### Example for an opened API (Display version Root action from AbstractController)
````php
     /**
     * @url GET /
     * @noauth
     */ 
````
GET call on / without Authentication

#### Example of POST without authentication (Login action from AuthController)
````php
     /**
     * @url POST /auth/login
     * @noauth
     */ 
````
POST call on /auth/login without Authentication

#### Example of DELETE a User with admin role (Delete action from UserController)
````php
     /**
     * @url DELETE /user/$id
     * @isadmin
     */ 
````
DELETE call on /user/{user_id} without Authentication