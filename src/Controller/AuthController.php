<?php

class AuthController extends AbstractController
{
    /**
     * @param PDO $pdo
     * @param ?Object $input
     */
    public function __construct(PDO $pdo, ?object $input)
    {
        parent::__construct($pdo, $input);
        $this->table = "user";
        $this->modelName = 'UserModel';
    }

    /**
     * @param UserModel $user
     * @return string
     */
    private static function getJwt (UserModel $user): string
    {
        $headers = array('alg' => 'HS256','typ' => 'JWT');
        $payload = array('user' => ['email' => $user->email, 'name' => $user->name, 'role' => $user->role], 'exp' => (time() + getenv('JWT_EXPIRATION')));
        return JwtService::create($headers, $payload, getenv('JWT_SECRET'));
    }

    /**
     * @return array
     *
     * @url POST /auth/login
     * @noauth
     * @throws Exception
     */
    public function loginAction(): array
    {
        if (property_exists($this->input, 'email') &&
            filter_var($this->input->email, FILTER_VALIDATE_EMAIL)
        ) {
            // Check if email exists
            $user = $this->search(['email' => $this->input->email], 1);
            if ($user instanceof UserModel) {
                if (property_exists($this->input, 'password')) {
                    if($user->password_verify($this->input->password)) {
                        return array('token' => self::getJwt($user));
                    } else {
                        throw new Exception("Invalid password", 422);
                    }
                } else {
                    throw new Exception("No Password", 422);
                }
            } else {
                throw new Exception("Email not found", 422);
            }
        } else {
            throw new Exception("Invalid Email", 422);
        }
    }

    /**
     *
     * @url POST /auth/register
     * @noauth
     * @throws Exception
     */
    public function registerAction(): array
    {
        if (!empty($this->input) &&
            property_exists($this->input, 'email') &&
            filter_var($this->input->email, FILTER_VALIDATE_EMAIL)
        ) {
            // Check if email exists
            $countEmail = $this->search(['email' => $this->input->email], null,true);
            if ($countEmail == 0) {

                if (property_exists($this->input, 'password') &&
                    property_exists($this->input, 'passwordConfirm') &&
                    $this->input->password == $this->input->passwordConfirm
                ) {
                    // First user: admin
                    $countAll = $this->search([], null,true);
                    $user = new UserModel();
                    $user->email = $this->input->email;
                    $user->password = $user->password_hash($this->input->password);
                    $user->name = $this->input->name;
                    $user->role = ($countAll == 0) ? "admin" : "default";
                    $this->create($user);

                    return array('token' => self::getJwt($user));
                } else {
                    throw new Exception("Password mismatch", 422);
                }
            } else {
                throw new Exception("Email already exists", 422);
            }
        } else {
            throw new Exception("Invalid Email", 422);
        }
    }

    /**
     * @return void
     *
     * @url POST /auth/logout
     * @noauth
     */
    public function logoutAction()
    {

    }

    /**
     * @return void
     *
     * @url POST /auth/requestpass
     * @noauth
     */
    public function requestpassAction()
    {

    }

    /**
     * @return void
     *
     * @url POST /auth/resetpass
     * @noauth
     */
    public function resetpassAction()
    {

    }

    /**
     * @return string[]
     *
     * @url POST /auth/refresh-token
     * @noauth
     */
    public function refreshTokenAction(): array
    {
        $token = $this->input->token;
        if (JwtService::isExpired($token)) {
            $tokenParts = explode('.', $token);
            $payload = base64_decode($tokenParts[1]);
            $email = json_decode($payload)->user->email;
            $user = $this->search(['email' => $email],1);
            $token = self::getJwt($user);
        }
        return ['token' => $token];
    }
}