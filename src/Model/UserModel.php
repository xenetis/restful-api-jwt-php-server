<?php
class UserModel extends AbstractModel{
    public int $id;
    public String $email;
    public String $password;
    public String $name;
    public String $role;

    /**
     * @param string $pwd
     * @return string
     */
    public function password_hash(string $pwd): string
    {
        $pwd_peppered = hash_hmac("sha256", $pwd, getenv('PWD_SECRET'));
        return password_hash($pwd_peppered, PASSWORD_ARGON2ID);
    }

    /**
     * @param string $pwd
     * @param ?string $pwd_hashed
     * @return bool
     */
    public function password_verify(string $pwd, string $pwd_hashed = null): bool
    {
        $pwd_peppered = hash_hmac("sha256", $pwd, getenv('PWD_SECRET'));
        if ($this->password && $pwd_hashed == null)
            return (password_verify($pwd_peppered, $this->password));
        else
            return (password_verify($pwd_peppered, $pwd_hashed));
    }

    /**
     * @return string[]
     */
    public function __hide(): array
    {
        return ['password'];
    }
}
