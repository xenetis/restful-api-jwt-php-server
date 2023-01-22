<?php

class UserController extends AbstractController {

    /**
     * @return array
     *
     * @url GET /user
     * @isadmin
     */
    public function getAllAction(): ?array
    {
        return parent::getAll();
    }

    /**
     * @return UserModel|null
     *
     * @url POST /user
     * @isadmin
     * @throws Exception
     */
    public function createAction(): ?UserModel
    {
        if (property_exists($this->input, 'email') &&
            filter_var($this->input->email, FILTER_VALIDATE_EMAIL)
        ) {
            // Check if email exists
            $user = $this->search(['email' => $this->input->email], 1);
            if ($user instanceof UserModel) {
                throw new Exception("Email already exists", 422);
            } else {
                $user = new UserModel();
                $user->email = $this->input->email;
//                $user->password = $user->password_hash($this->input->password);
                $user->password = $user->password_hash('azerty');
                $user->name = $this->input->name;
                $user->role = $this->input->role;
                parent::create($user);
                return parent::search(['email' => $user->email], 1);
            }
        } else {
            throw new Exception("Invalid Email", 422);
        }
    }

    /**
     * @return UserModel|null
     *
     * @url PUT /user/$id
     * @isadmin
     * @throws Exception
     */
    public function updateAction($id): ?UserModel
    {
        $user = $this->search(['id' => $id], 1);
        if ($user instanceof UserModel) {
            if (property_exists($this->input, 'email') &&
                filter_var($this->input->email, FILTER_VALIDATE_EMAIL)
            ) {
                $user->email = $this->input->email;
                $user->name = $this->input->name;
                $user->role = $this->input->role;
                if (property_exists($this->input, 'password') &&
                    property_exists($this->input, 'passwordConfirm') &&
                    $this->input->password == $this->input->passwordConfirm) {
                    $user->password = $user->password_hash($this->input->password);
                }
                parent::update($user);
                return parent::search(['email' => $user->email], 1);
            } else {
                throw new Exception("Invalid Email", 422);
            }
        } else {
            throw new Exception("No account for this Id", 422);
        }
    }

    /**
     * @param string|null $id
     * @return void
     *
     * @throws Exception
     * @url DELETE /user/$id
     * @isadmin
     */
    public function deleteAction(string $id = null): bool
    {
        if ($id) {
            $user = $this->search(['id' => $id], 1);
            if ($user instanceof UserModel) {
                $this->delete($user->id);
                return true;
            } else {
                throw new Exception("No account for this Email", 422);
            }
        } else {
            throw new Exception("No email provided", 422);
        }
    }

    /**
     * @return void
     *
     * @url DELETE /user/reset
     * @isadmin
     */
    public function resetAction(): void
    {
        $users = self::getAll();
        if(count($users)) {
            foreach ($users as $user) {
                $this->delete($user->id);
            }
        }
    }

}

