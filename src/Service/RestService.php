<?php

/**
 * Class RestService
 */
class RestService
{

    const FORMATS = [
        'plain' => 'text/plain',
        'txt'   => 'text/plain',
        'html'  => 'text/html',
        'json'  => 'application/json',
        'xml'   => 'application/xml',
    ];

    private array $codes = array(
        '100' => 'Continue',
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '307' => 'Temporary Redirect',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested Range Not Satisfiable',
        '417' => 'Expectation Failed',
        '422' => 'Unprocessable Entity',
        '498' => 'Token expired or Invalid',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '503' => 'Service Unavailable'
    );

    private ?string $route = null;
    private ?string$fullroute = null;
    private $format = null;
    private $method = null;
    private array $map = [];
    private PDO $pdo;

    /**
     * @var string | array
     */
    public $allowedOrigin = 'http://localhost:3000';
    public $useCors = null;
    /**
     * @var mixed|stdClass
     */
    private $input;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        // Check cors
        $this->useCors = (getenv('USE_CORS') !== false) ? getenv('USE_CORS') : false;

        // Check format
        if (getenv('API_FORMAT') && array_key_exists(getenv('API_FORMAT'), self::FORMATS))
            $this->format = getenv('API_FORMAT');
        else
            $this->handleError(400, 'BAD API FORMAT');

        // check route
        if ($_SERVER['REQUEST_URI'] == getenv('API_ROOT')) {
            header("Location: " . getenv('API_ROOT') . "/",TRUE,307);
            exit;
        }
        if (strpos($_SERVER['REQUEST_URI'], getenv('API_ROOT') . "/") === 0)
            $this->setRoute($_SERVER['REQUEST_URI']);
        else
            $this->handleError(400,'BAD ROUTE');

        $this->method = $this->getMethod();

        $this->controllerMapping();

        $this->handle();
    }

    /**
     * @param numeric $statusCode
     * @param string | null $errorMessage
     * @return void
     */
    public function handleError($statusCode, string $errorMessage = null) {
        $data = array(
            'error' => array(
                'code' => $statusCode,
                'message' => $errorMessage ?: $this->codes[$statusCode]
            )
        );

        if (getenv('DEBUG')) {
            error_log("CALL : " . $this->getMethod() . ": " . $this->getRoute() . " | ERROR: " . $statusCode . ": " . $data['error']['message']);
        }

        $this->setStatus($statusCode);
        $this->sendData($data);
        exit;
    }

    public function sendData($data) {
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: 0");
        header('Content-Type: ' . self::FORMATS[$this->format]);

        if ($this->useCors) {
            $this->corsHeaders();
        }

//        if (getenv('DEBUG')) $data['debug'] = $this->debug();

        if (is_object($data) && method_exists($data, '__hide')) {
            $data = clone $data;
            foreach ($data->__hide() as $prop) {
                unset($data->$prop);
            }
        }
        if(is_array($data) && count($data)) {
            foreach ($data as $k => $v) {
                if (is_object($v) && method_exists($v, '__hide')) {
                    $v = clone $v;
                    foreach ($v->__hide() as $prop) {
                        unset($v->$prop);
                        $data[$k] = $v;
                    }
                }
            }
        }

        if ($this->format == "xml") {
            $this->xml_encode($data);
        } else {
            $options = 0;
            if (getenv("DEBUG") && defined('JSON_PRETTY_PRINT')) {
                $options = JSON_PRETTY_PRINT;
            }
            if (defined('JSON_UNESCAPED_UNICODE')) {
                $options = $options | JSON_UNESCAPED_UNICODE;
            }
            echo json_encode($data, $options);
        }
    }

    public function setStatus($code) {
        echo "code " . $code;
        http_response_code($code);
    }

    private function corsHeaders() {
        // Force array for multiple origin
        $allowedOrigin = (array)$this->allowedOrigin;

        // if no origin header is present then requested origin can be anything (i.e *)
        $currentOrigin = !empty($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';

        // One match, single origin is enough
        if (in_array($currentOrigin, $allowedOrigin)) {
            $allowedOrigin = array($currentOrigin);
        }

        // For multiple origin
        foreach($allowedOrigin as $allowed_origin) {
            header("Access-Control-Allow-Origin: $allowed_origin");
        }

        header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
        header('Access-Control-Allow-Credential: true');
        header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers, Authorization');
    }

    public function getMethod() {
        $method = $_SERVER['REQUEST_METHOD'];
        $override = isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] : (isset($_GET['method']) ? $_GET['method'] : '');

        if ($method == 'POST' && strtoupper($override) == 'PUT') {
            $method = 'PUT';
        } else if ($method == 'POST' && strtoupper($override) == 'DELETE') {
            $method = 'DELETE';
        } else if ($method == 'POST' && strtoupper($override) == 'PATCH') {
            $method = 'PATCH';
        }
        return $method;
    }

    /**
     * @param false|string|null $route
     */
    public function setRoute($route): void
    {
        $route = substr($route, strlen(getenv('API_ROOT')), strlen($route));
        $route = preg_replace('/\?.*$/', '', $route);
        $this->route = $route;
        $this->fullroute = getenv('API_ROOT') . $this->route;
    }

    /**
     * @return string|null
     */
    public function getRoute(): ?string
    {
        return $this->route;
    }

    /**
     * @param $map
     * @return bool
     */
    public function isAuthorized($map): bool
    {
        // Free API
        if (getenv('IS_AUTH') === false) return true;

        // Not auth API
        if(in_array("noauth", $map)) return true;

        // Auth valid
        if (JwtService::get_bearer_token() && JwtService::isValid(JwtService::get_bearer_token(), getenv('JWT_SECRET'))) {

            // Is Admin API
            if(in_array("isadmin", $map)) {
                $tokenParts = explode('.', JwtService::get_bearer_token());
                $payload = base64_decode($tokenParts[1]);
                $email = json_decode($payload)->user->email;
                $userControler = new UserController($this->pdo);
                $user = $userControler->search(["email" => $email],1);
                return ($user instanceof UserModel && $user->role == 'admin');
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public function handle()
    {
        if ($this->method == 'OPTIONS') {
            if ($this->useCors) {
                $this->corsHeaders();
            }
            if (array_key_exists('Access-Control-Request-Headers', getallheaders())) {
                $this->setStatus(200);
                $this->sendData([]);
            }
            exit;
        }

        $this->input = ($this->method == 'PUT' || $this->method == 'POST' || $this->method == 'PATCH')
            ? json_decode(file_get_contents('php://input'))
            : new stdClass();

        if (array_key_exists($this->method, $this->map)) {

            // Route without variable
            $routeFound = array_key_exists($this->fullroute, $this->map[$this->method]);
            $mapFound = null;

            if ($routeFound) {
                $mapFound = $this->map[$this->method][$this->fullroute];
            } else {
                $explodedRoute = explode("/", substr($this->fullroute, 1));
                if(count($this->map[$this->method])) {
                    foreach ($this->map[$this->method] as $mapKey => $mapTemp) {
                        if(strpos($mapKey, '$') !== false) {
                            // /api/user/$id => /api/user/
                            $mapKeyShort = substr($mapKey, 0, strpos($mapKey, '$'));

                            if(strpos($this->fullroute, $mapKeyShort) === 0 && !empty($mapTemp[2])) {
                                $explodedParams = explode("/", substr($this->fullroute, strlen($mapKeyShort)));
                                if(count($explodedParams) == count($mapTemp[2])) {
                                    $mapFound = $mapTemp;
                                    $mapFound[2] = $explodedParams;
                                }
                            }
                        }
                    }
                }
            }

            if ($mapFound) {
//                var_dump($mapFound);
//                if (JwtService::get_bearer_token() &&
//                    JwtService::isExpired(JwtService::get_bearer_token())) {
//                    $this->handleError(498);
//                }
                if(self::isAuthorized($mapFound)) {
                    try {
//                        $controller = new $this->map[$this->method][$this->fullroute][0]($this->pdo, $this->input);
//                        $data = $controller->{$this->map[$this->method][$this->fullroute][1]}();
                        $controller = new $mapFound[0]($this->pdo, $this->input);
                        if(empty($mapFound[2]))
                            $data = $controller->{$mapFound[1]}();
                        else
                            $data = $controller->{$mapFound[1]}(implode(',', $mapFound[2]));
                        $this->sendData($data);
                    } catch (Exception $e) {
                        if(is_int($e->getCode()))
                            $this->handleError($e->getCode(), $e->getMessage());
                        else
                            $this->handleError(422, '[error_dode: ' . $e->getCode() . '] ' . $e->getMessage());
                    }
                } else {
                    $this->handleError(401);
                }
            } else {
                $this->handleError(405);
            }
        } else {
            $this->handleError(405);
        }
    }

    public function controllerMapping(): array
    {
        $controllerMapCache = APPLICATION_PATH . '/../data/.controllerMap.cache';

        // Nocache in debug
        if (is_file($controllerMapCache) && getenv('DEBUG'))
            @unlink($controllerMapCache);

        if (is_file($controllerMapCache) && !getenv('DEBUG')) {
            // Load cache
            $this->map = unserialize(file_get_contents($controllerMapCache));
        } else {
            // Create cache
            if (is_dir(APPLICATION_PATH . '/Controller')) {
                $controllerFiles = glob(APPLICATION_PATH . '/Controller/*.php', GLOB_BRACE);
                if(count($controllerFiles)) {
                    foreach($controllerFiles as $controllerFile) {
                        $controller = basename(str_replace(".php","",$controllerFile));
                        if (class_exists($controller)) {
                            $reflection = new ReflectionClass($controller);
                            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                            foreach ($methods as $method) {
                                $doc = $method->getDocComment();
                                $noAuth = strpos($doc, '@noauth') !== false;
                                $isadmin = strpos($doc, '@isadmin') !== false;
                                if (preg_match_all('/@url[ \t]+(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)[ \t]+(\S*)/s', $doc, $matches, PREG_SET_ORDER)) {
                                    $params = $method->getParameters();

                                    foreach ($matches as $match) {
                                        $httpMethod = $match[1];
                                        $url = getenv('API_ROOT') . $match[2];

                                        $api = array($controller, $method->getName());
                                        $args = array();

                                        foreach ($params as $param) {
                                            $args[$param->getName()] = $param->getPosition();
                                        }

                                        $api[] = $args;
                                        if ($noAuth) $api[] = "noauth";
                                        if ($isadmin) $api[] = "isadmin";

                                        $this->map[$httpMethod] = (array_key_exists($httpMethod, $this->map)) ? $this->map[$httpMethod] : [];
                                        $this->map[$httpMethod][$url] = (array_key_exists($url, $this->map[$httpMethod])) ? $this->map[$httpMethod][$url] : $api;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if(!getenv('DEBUG'))
                file_put_contents($controllerMapCache, serialize($this->map));
        }
        return $this->map;
    }

    /**
     * @return array Debug info
     */
    public function debug(): array
    {
        if(property_exists($this->input,'password')) $this->input->password = '****************';
        if(property_exists($this->input,'passwordConfirm')) $this->input->passwordConfirm = '****************';

        return [
            'REQUEST_URI' => $_SERVER['REQUEST_URI'],
            'API_ROOT_ENV' => getenv('API_ROOT'),
            'ROUTE' => $this->getRoute(),
            'FORMAT' => self::FORMATS[$this->format],
            'MAP' => $this->map,
            'INPUT' => $this->input
        ];
    }

    /**
     * @param $mixed
     * @param $domElement
     * @param $DOMDocument
     * @return void
     */
    private function xml_encode($mixed, $domElement = null, $DOMDocument = null): void
    {
        if (is_null($DOMDocument)) {
            $DOMDocument = new DOMDocument;
            $DOMDocument->formatOutput = true;
            $this->xml_encode($mixed, $DOMDocument, $DOMDocument);
            echo $DOMDocument->saveXML();
        } else if (is_null($mixed) || $mixed === false || (is_array($mixed) && empty($mixed))) {
            $domElement->appendChild($DOMDocument->createTextNode(null));
        } else if (is_array($mixed)) {
            foreach ($mixed as $index => $mixedElement) {
                if (is_int($index)) {
                    if ($index === 0) {
                        $node = $domElement;
                    } else {
                        $node = $DOMDocument->createElement($domElement->tagName);
                        $domElement->parentNode->appendChild($node);
                    }
                } else {
                    $index = str_replace(' ', '_', $index);
                    $plural = $DOMDocument->createElement($index);
                    $domElement->appendChild($plural);
                    $node = $plural;

                    if (!(rtrim($index, 's') === $index) && !empty($mixedElement)) {
                        $singular = $DOMDocument->createElement(rtrim($index, 's'));
                        $plural->appendChild($singular);
                        $node = $singular;
                    }
                }

                $this->xml_encode($mixedElement, $node, $DOMDocument);
            }
        } else {
            $domElement->appendChild($DOMDocument->createTextNode($mixed));
        }
    }

}