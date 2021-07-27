<?php
class AccountApiController extends \devpirates\MVC\Base\ApiController {
    use AuthorizedApiController;

    /**
     * @var UserHelper
     */
    private $helper;

    public function __construct() {
        $this->_init();
        $this->helper = new UserHelper();
        parent::__construct();
    }

    /**
     * Returns basic user information for
     *
     * @return void
     */
    public function Index(): void {
        if ($this->isLoggedIn() === true) {
            $this->respond(array("IsLoggedIn" => true, "Username" => $this->getCurrentUsername()));
        } else {
            $this->respond(array("IsLoggedIn" => false));
        }
    }

    /**
     * First step to 2 factor setup for an account
     * Returns data required to set up 2 factor via the authenticator app
     *
     * @return void
     */
    public function Info(): void {
        $this->authorize(function() {
            $user = $this->getCurrentUser();
            $this->respond(array(
                "Username" => $user->Username,
                "Email" => $user->Email,
                "Require2FA" => $user->Require2FA
            ));
        });
    }

    /**
     * Checks the username and password against what's in the db to decide if the user should be able to log in
     *
     * @return void
     */
    public function Login(): void {
        $errorMessage = "Username and password are required.";
        if (isset(REQUEST_POST['payload']) && strlen(REQUEST_POST['payload'])) {
            $errorMessage = "Username or password provided could not be validated.";
            $credSplit = explode("|::|", base64_decode(REQUEST_POST['payload']), 2);
            if ($this->helper->UserExists($credSplit[0]) === true) {
                $user = $this->helper->GetUserByUsername($credSplit[0]);
                if (isset($user)) {
                    if (Authentication::CheckPassword($credSplit[1], $user->PasswordSalt, $user->PasswordHash) === true) {
                        if ($user->Require2FA === true) {
                            $twoFactorRequest = GUIDHelper::GUIDv4();
                            $_SESSION[Constants::TWO_FACTOR_SESSION] = $twoFactorRequest;
                            $_SESSION[$twoFactorRequest] = base64_encode($user->Username);
                            $this->respond(ResponseInfo::Success("2FA", $twoFactorRequest));
                        } else {
                            $this->setCurrentUser($user);
                            $this->respond(ResponseInfo::Success());
                        }
                        return;
                    }
                }
            }
        }
        $this->respond(ResponseInfo::Error($errorMessage));
    }

    /**
     * Second step in login via 2FA, compares code provided with the secret stored on the user
     *
     * @return void
     */
    public function Login2FA(): void {
        $this->throttle("2FA", 5, 1, function() {
            if (isset(REQUEST_POST['2fa']) && isset($_SESSION[Constants::TWO_FACTOR_SESSION]) && REQUEST_POST['2fa'] === $_SESSION[Constants::TWO_FACTOR_SESSION]) {
                if (isset($_SESSION[$_SESSION[Constants::TWO_FACTOR_SESSION]]) && strlen($_SESSION[$_SESSION[Constants::TWO_FACTOR_SESSION]])) {
                    $username = base64_decode($_SESSION[$_SESSION[Constants::TWO_FACTOR_SESSION]]);
                    unset($_SESSION[$_SESSION[Constants::TWO_FACTOR_SESSION]]);
                    unset($_SESSION[Constants::TWO_FACTOR_SESSION]);
                    if (isset(REQUEST_POST['code']) && strlen(REQUEST_POST['code'])) {
                        $user = $this->helper->GetUserByUsername($username);
                        if (isset($user) === true) {
                            $g = new Sonata\GoogleAuthenticator\GoogleAuthenticator();
                            if ($g->checkCode($user->Secret2FA, REQUEST_POST['code']) === true) {
                                $_SESSION['authenticated'] = base64_encode($user->Username . '||' . $user->Uid);
                                $this->respond(ResponseInfo::Success());
                            } else {
                                $twoFactorRequest = GUIDHelper::GUIDv4();
                                $_SESSION[Constants::TWO_FACTOR_SESSION] = $twoFactorRequest;
                                $_SESSION[$twoFactorRequest] = base64_encode($user->Username);
                                $response = new ResponseInfo(false, $twoFactorRequest, "Code could not be verified.");
                                $this->respond($response);
                            }
                            return;
                        }
                    }
                }
            }
            $this->respond(ResponseInfo::Error("Failed to authenticate provided code."));
        });
    }

    /**
     * First step to 2 factor setup for an account
     * Returns data required to set up 2 factor via the authenticator app
     *
     * @return void
     */
    public function Get2FactorSetup(): void {
        $this->authorize(function() {
            $username = $this->getCurrentUsername();
            $g = new Sonata\GoogleAuthenticator\GoogleAuthenticator();
            $secret = $g->generateSecret();
            $_SESSION["2FASetupSecret"] = $secret;
            $this->respond(ResponseInfo::Success(null, Sonata\GoogleAuthenticator\GoogleQrUrl::generate($username, $secret, CONSTANTS::SITE_NAME_CLEAN)));
        });
    }

    /**
     * Final step to setting up 2 factor for a user
     * Takes the $code provided from the authenticator app to authenticate the setup
     *
     * @param string $code
     * @return void
     */
    public function Enable2FactorSetup(string $code): void {
        $this->authorize(function($c) {
            if (isset($_SESSION["2FASetupSecret"])) {
                $secret = $_SESSION["2FASetupSecret"];
                $g = new Sonata\GoogleAuthenticator\GoogleAuthenticator();
                if ($g->checkCode($secret, $c)) {
                    $user = $this->getCurrentUser();
                    $user->Secret2FA = $secret;
                    $user->Require2FA = true;
                    $response = $this->helper->UpsertUser($user);
                    if ($response->Success === true) {
                        unset($_SESSION["2FASetupSecret"]);
                        $this->respond(ResponseInfo::Success());
                    } else {
                        $this->respond(ResponseInfo::Error("Unable to update your user account. Please try again, or contact us for assistance."));
                    }
                } else {
                    $this->respond(ResponseInfo::Error("Invalid code, please try again."));
                }
            } else {
                $this->respond(ResponseInfo::Error("Unable to verify 2 Factor request. Please start over."));
            }
        }, $code);
    }

    /**
     * Disables 2 factor for the current account if the password provided matches
     *
     * @return void
     */
    public function Disable2Factor(): void {
        $this->authorize(function() {
            if (isset(REQUEST_POST["password"])) {
                $password = REQUEST_POST["password"];
                $user = $this->getCurrentUser();
                if (Authentication::CheckPassword($password, $user->PasswordSalt, $user->PasswordHash)) {
                    $user->Secret2FA = null;
                    $user->Require2FA = false;
                    $response = $this->helper->UpsertUser($user);
                    if ($response->Success === true) {
                        $this->respond(ResponseInfo::Success());
                    } else {
                        $this->respond(ResponseInfo::Error("Unable to update your user account. Please try again, or contact us for assistance."));
                    }
                } else {
                    $this->respond(ResponseInfo::Error("Invalid password, please try again."));
                }
            } else {
                $this->respond(ResponseInfo::Error("Password is required."));
            }
        });
    }

    /**
     * Verifies if the provided password is correct for the currently logged in user.
     *
     * @return void
     */
    public function VerifyPassword(): void {
        $this->authorize(function() {
            if (isset(REQUEST_POST["password"])) {
                $password = REQUEST_POST["password"];
                $user = $this->getCurrentUser();
                if (Authentication::CheckPassword($password, $user->PasswordSalt, $user->PasswordHash)) {
                    $this->respond(ResponseInfo::Success());
                } else {
                    $this->respond(ResponseInfo::Error("Invalid password, please try again."));
                }
            } else {
                $this->respond(ResponseInfo::Error("Password is required."));
            }
        });
    }

    /**
     * Sends the forgot password email to the provided email address
     *
     * @return void
     */
    public function BeginResetPassword(): void {
        $this->throttle("BeginResetPassword", 1, 1, function() {
            $this->helper->BeginResetPassword();
            $this->respond(ResponseInfo::Success());
        });
    }

    /**
     * Finalizes the account password reset
     *
     * @return void
     */
    public function ResetPassword(): void {
        $this->throttle("ResetPassword", 2, 5, function() {
            $this->respond($this->helper->ResetPassword());
        });
    }

    /**
     * Instantiates intitial user account, and sends registration email to new user
     *
     * @return void
     */
    public function BeginRegister(): void {
        $this->throttle("BeginRegister", 2, 1, function() {
            $this->respond($this->helper->BeginRegister());
        });
    }

    /**
     * Final registration step
     *
     * @return void
     */
    public function FinishRegister(): void {
        $this->throttle("FinishRegister", 5, 1, function() {
            $this->respond($this->helper->FinishRegister());
        });
    }
}
?>