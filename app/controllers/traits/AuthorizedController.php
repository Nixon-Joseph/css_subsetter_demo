<?php
trait AuthorizedController {
    private $isLoggedIn;
    private $executingUsername;
    private $executingUserid;

    /**
     * Must call this function in your controller before using any of the other functions
     *
     * @return void
     */
    protected function _init(): void {
        if (AuthenticationHelper::IsLoggedIn()) {
            $this->isLoggedIn = true;
            $authData = AuthenticationHelper::GetAuthData();
            $this->executingUserid = $authData['id'];
            $this->executingUsername = $authData['username'];
        } else {
            $this->isLoggedIn = false;
            $this->executingUserid = null;
            $this->executingUsername = null;
        }
    }

    /**
     * Executes passed in function if the user is authorized to execute it
     * Authorization is whether or not the user is logged in, or in one of the optionally passed in roles
     *
     * @param callable $action
     * @param any|null $callableParams
     * @param array|null $roles
     * @return void
     */
    protected function authorize(callable $action, $callableParams = null, ?array $roles = null, string $redirectUrl = "/account/login"): void {
        if ($this->isLoggedIn() === true) {
            if (isset($roles) === false || $roles === null || sizeof($roles) === 0) {
                if (isset($callableParams)) {
                    $action($callableParams);
                } else {
                    $action();
                }
                return;
            } else {
                $userId = $this->getCurrentUserid();
                // $roleHelper = new RoleHelper();
                // get roles for user
                // if user is in any of the passed in roles, run action, and return
            }
        }
        header("location: $redirectUrl");
    }

    /**
     * Returns username of currently logged in user
     *
     * @return string|null
     */
    protected function getCurrentUsername(): ?string {
        return $this->executingUsername;
    }

    /**
     * Returns user id of currently logged in user
     *
     * @return int|null
     */
    protected function getCurrentUserid(): ?int {
        return $this->executingUserid;
    }

    /**
     * Returns whether or not the current user is logged in
     *
     * @return boolean
     */
    protected function isLoggedIn(): bool {
        return $this->isLoggedIn;
    }

    /**
     * Sets the session variable containing the user information
     *
     * @param User $user
     * @return void
     */
    protected function setCurrentUser(?User $user): void {
        if (isset($user) && $user !== null) {
            AuthenticationHelper::SetCurrentUser($user);
            $this->isLoggedIn = true;
            $this->executingUsername = $user->Username;
            $this->executingUserid = $user->Uid;
        } else {
            $this->clearCurrentUser();
        }
    }

    /**
     * 'logout'
     *
     * @return void
     */
    protected function clearCurrentUser(): void {
        AuthenticationHelper::SetCurrentUser(null);
        $this->isLoggedIn = false;
        $this->executingUsername = null;
        $this->executingUserid = null;
    }

    /**
     * Returns the user object for whoever is logged in
     *
     * @return User|null
     */
    protected function getCurrentUser(): ?User {
        if ($this->isLoggedIn === true) {
            $helper = new UserHelper();
            return $helper->GetUserByUsername($this->executingUsername);
        } else {
            return null;
        }
    }
}
?>