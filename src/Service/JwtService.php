<?php

class JwtService {


    /**
     * @param array $headers
     * @param array $payload
     * @param string $secret
     * @return string
     */
    public static function create(array $headers, array $payload, string $secret = 'secret'): string
    {
        $headers_encoded = self::base64url_encode(json_encode($headers));

        $payload_encoded = self::base64url_encode(json_encode($payload));

        $signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
        $signature_encoded = self::base64url_encode($signature);

        return "$headers_encoded.$payload_encoded.$signature_encoded";
    }

    /**
     * @param string $jwt
     * @return bool
     */
    public static function isExpired(string $jwt): bool
    {
        // split the jwt
        $tokenParts = explode('.', $jwt);
        $payload = base64_decode($tokenParts[1]);

        // check the expiration time - note this will cause an error if there is no 'exp' claim in the jwt
        $expiration = json_decode($payload)->exp;
        return ($expiration - time()) < 0;
    }

    /**
     * @param string $jwt
     * @param string $secret
     * @return bool
     */
    public static function isValid(string $jwt, string $secret = 'secret'): bool
    {
        // split the jwt
        $tokenParts = explode('.', $jwt);
        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signature_provided = $tokenParts[2];

        // check the expiration time - note this will cause an error if there is no 'exp' claim in the jwt
        $expiration = json_decode($payload)->exp;
        $is_token_expired = ($expiration - time()) < 0;

        // build a signature based on the header and payload using the secret
        $base64_url_header = self::base64url_encode($header);
        $base64_url_payload = self::base64url_encode($payload);
        $signature = hash_hmac('SHA256', $base64_url_header . "." . $base64_url_payload, $secret, true);
        $base64_url_signature = self::base64url_encode($signature);

        // verify it matches the signature provided in the jwt
        $is_signature_valid = ($base64_url_signature === $signature_provided);

        return (!$is_token_expired && $is_signature_valid);
    }

    /**
     * @param string $data
     * @return string
     */
    private static function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function get_authorization_header(): ?string
    {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } else if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        return $headers;
    }

    public static function get_bearer_token() {
        $headers = self::get_authorization_header();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}