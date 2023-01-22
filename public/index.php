<?php
    /**
     * @author xenetis
     */

    // Define path to application directory
    defined('APPLICATION_PATH')  ||
    define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../src'));

    // Bootstrap autoload
    spl_autoload_register(function($class) {
        $matches = preg_split('/([[:upper:]][[:lower:]]+)/', $class, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        switch (count($matches)) {
            case 2:
                if (in_array($matches[1], ['Controller', 'Service', 'Model'])
                    && file_exists(APPLICATION_PATH . '/' . $matches[1] . '/' . $matches[0] . $matches[1] . '.php')) {
                    require_once APPLICATION_PATH . '/' . $matches[1] . '/' . $matches[0] . $matches[1] . '.php';
                }
                break;
            case 1:
                if (file_exists(APPLICATION_PATH . '/Class/' . $matches[0] . '.php')) {
                    require_once APPLICATION_PATH . '/Class/' . $matches[0] . '.php';
                }
                break;
            case 0:
            default:
                break;
        }
    });

    function var_error_log( $object=null ){
        ob_start();                    // start buffer capture
        var_dump( $object );           // dump the values
        $contents = ob_get_contents(); // put the buffer into a variable
        ob_end_clean();                // end capture
        error_log( $contents );        // log contents of the result of var_dump( $object )
    }

    // Dotenv class
    new Dotenv(APPLICATION_PATH . '/../.env');

    // PDO
    $dbType = getenv('DB_TYPE');
    $pdo = new $dbType();

    // Restful Service
    $restService = new RestService($pdo);
