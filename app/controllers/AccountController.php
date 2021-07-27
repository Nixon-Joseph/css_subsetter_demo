<?php
class AccountController extends Controller {
    use AuthorizedController;

    public function __construct() {
        $this->_init();
        parent::__construct();
    }

    public function Index(): void {
        $this->authorize(function() {
            // $this->scripts[] = "/public/scripts/account/index.js";
            // $user = $this->getCurrentUser();
            // $model = new AccountVM();
            // $model->Username = $user->Username;
            // $model->Enabled2FA = $user->Require2FA;
            // $this->view($model);
            $this->view(null, "./public/scripts/angular/account/index.html");
        });
    }

    public function Login(): void {
        $this->scripts[] = "/public/scripts/account/login.js";
        $this->view();
    }

    public function Logout(): void {
        $this->clearCurrentUser();
        header('location: /account/login');
    }

    public function ResetPassword(): void {
        $this->scripts[] = "/public/scripts/account/resetpassword.js";
        $this->view(['Code' => REQUEST_GET['code']]);
    }

    public function Create(): void {
        $this->scripts[] = "/public/scripts/account/create.js";
        $this->view(['Code' => REQUEST_GET['code']]);
    }
}
?>