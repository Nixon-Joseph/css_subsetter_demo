<?php
class UserHelper extends \devpirates\MVC\Base\Helper {
    /**
     * @var UserRepo
     */
    private $repo;

    /**
     * Regex for email addresses
     *
     * @var Regex
     */
    private $emailRegex = '/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD';
    /**
     * Regex for testing username
     *
     * @var string
     */
    private $invalidUsernameRegex = '/[\W]/';

    public function __construct() {
        $this->repo = new UserRepo();
    }

    public function BeginResetPassword(): void {
        $email = REQUEST_GET['email'];
        if ($this->UserEmailExists($email) === true) {
            $user = $this->GetUserByEmail($email);
            if (isset($user)) {
                $requestHelper = new PasswordResetRequestHelper();
                $requestResponse = $requestHelper->CreateRequest($user->Uid);
                if ($requestResponse->Success === true) {
                    LogHelper::WriteLog("Reset Password requested for $email", LogTypes::EMAIL);
                    $url = Constants::SITE_ADDRESS . "/account/resetpassword?code=" . $requestResponse->Message;
                    $emailContents = EmailHelper::BuildEmail(Files::OpenFile("./app/emails/PasswordReset.html"), array(
                        'name' => $user->Username,
                        'action_url' => $url
                    ));
                    $emailSuccess = EmailHelper::SendEmail($emailContents, "New Password Reset Request", $email, Constants::FROM_EMAIL, true);
                }
            }
        } else {
            LogHelper::WriteLog("Reset Password requested for unkown email: $email", LogTypes::EMAIL);
        }
    }

    public function ResetPassword(): ResponseInfo {
        $response = ResponseInfo::Error("Could not find referenced password reset request.");
        if (isset(REQUEST_POST['code'])) {
            $code = REQUEST_POST['code'];
            $requestHelper = new PasswordResetRequestHelper();
            $request = $requestHelper->GetRequest($code);
            if (isset($request)) {
                $email = REQUEST_POST['email'];
                $user = $this->GetUserByEmail($email);
                if (isset($user)) {
                    if (time() - strtotime($request->Date) > (60 * 60 * 24)) { // 24 hours
                        $response->Message = "This password reset link has expired. Please generate a new one, and try again.";
                    } else {
                        if ($request->User === $user->Uid) {
                            $newPassword = REQUEST_POST['password'];
                            if (isset($newPassword)) {
                                $newPasswordInfo = Authentication::GenerateHashAndSalt($newPassword);
                                $user->PasswordHash = $newPasswordInfo['hash'];
                                $user->PasswordSalt = $newPasswordInfo['salt'];
                                $userUpdateResponse = $this->UpsertUser($user);
                                if ($userUpdateResponse->Success === true) {
                                    try {
                                        $requestHelper->DeleteRequest($request->Uid);
                                    } catch (\Throwable $th) { }
                                    $response->Success = true;
                                    $response->Message = null;
                                } else {
                                    $response->Message = "Could not update your password. Please try again, or contact us for assistance.";
                                }
                            }
                        } else {
                            $response->Message = "Could not find reset request for user with email: $email";
                        }
                    }
                }
            }
        }
        return $response;
    }

    public function BeginRegister(): ResponseInfo {
        $response = ResponseInfo::Error("No email address was provided.");
        if (isset(REQUEST_GET['email'])) {
            $email = trim(REQUEST_GET['email']);
            $matches = preg_match($this->emailRegex, $email);
            if ($matches === 1) {
                $userExists = $this->UserEmailExists($email);
                if ($userExists === true) {
                    return ResponseInfo::Error("A user with the provided email already exists. Did you <a data-dismiss='modal' class='text-console-secondary forgot-password-link' href='#'>forget your password?</a>");
                } else {
                    $user = new User();
                    $user->Email = $email;
                    $user->Username = GUIDHelper::GUIDv4();
                    $user->Require2FA = false;
                    $insertResponse = $this->UpsertUser($user);
                    if ($insertResponse->Success === true) {
                        $response->Success = true;
                        //LogHelper::WriteLog("Reset Password requested for $email", LogTypes::EMAIL);
                        $url = Constants::SITE_ADDRESS . "/account/create?code=" . $user->Username;
                        $emailContents = EmailHelper::BuildEmail(Files::OpenFile("./app/emails/NewAccount_Pre.html"), array('action_url' => $url));
                        $emailSuccess = EmailHelper::SendEmail($emailContents, "New Account Request", $email, Constants::FROM_EMAIL, true);
                        // if email succeeds
                        if ($emailSuccess === true) {
                            $response->Message = "";
                        } else {
                            $response->Message = "Initial registration successful, but we could not send the registration email. Please contact us for assistance.";
                        }
                    } else {
                        $response->Message = "Unable to register the provided email. If this issue persists, please contact us for assitance.";
                    }
                }
            } else {
                $response->Message = "Unable to validate the provided email. Please double check that the provided email address. If this is your actual email, contact us for assitance.";
            }
        }
        return $response;
    }

    public function FinishRegister(): ResponseInfo {
        $response = ResponseInfo::Error("Could not find account request.");
        if (isset(REQUEST_POST['code'])) {
            $code = REQUEST_POST['code'];
            $user = $this->repo->GetByUsername($code);
            if (isset($user)) {
                $email = REQUEST_POST['email'];
                $matches = preg_match($this->emailRegex, $email);
                if ($matches === 1 && strtolower($user->Email) === strtolower($email)) {
                    $username = REQUEST_POST['username'];
                    if (isset($username) && strlen($username) > 0) {
                        $matches = preg_match($this->invalidUsernameRegex, $username);
                        if ($matches === 0) {
                            if ($this->UserExists($username) === false) {
                                $password = REQUEST_POST['password'];
                                if (isset($password) && strlen($password) > 0) {
                                    $passwordInfo = Authentication::GenerateHashAndSalt($password);
                                    $user->PasswordHash = $passwordInfo['hash'];
                                    $user->PasswordSalt = $passwordInfo['salt'];
                                    $user->Username = $username;
                                    $userUpdateResponse = $this->UpsertUser($user);
                                    if ($userUpdateResponse->Success === true) {
                                        $response->Success = true;
                                        $response->Message = null;
                                        $emailContents = EmailHelper::BuildEmail(Files::OpenFile("./app/emails/NewAccount.html"), array(
                                            'name' => $username,
                                            'action_url' => Constants::SITE_ADDRESS . "/account/login"
                                        ));
                                        $emailSuccess = EmailHelper::SendEmail($emailContents, "Welcome to " . Constants::SITE_NAME_CLEAN, $email, Constants::FROM_EMAIL, true);
                                    } else {
                                        $response->Message = "Could not finish setting up your profile. Please try again, or contact us for assistance.";
                                    }
                                } else {
                                    $response->Message = "Password is required.";    
                                }
                            } else {
                                $response->Message = "The requested username <strong>'$username'</strong> is already in use. Please select a different username.";
                            }
                        } else {
                            $response->Message = "Username is invalid. Only alpha-numeric characters are allowed.";    
                        }
                    } else {
                        $response->Message = "Username is required.";    
                    }
                } else {
                    $response->Message = "Unable to validate the provided email. Please double check that the provided email address.";
                }
            }
        }
        return $response;
    }

    public function UserExists(string $username): bool {
        return $this->repo->UsernameExists($username);
    }

    public function GetUserByUsername(string $username): User {
        return $this->repo->GetByUsername($username);
    }

    public function GetUserByEmail(string $username): User {
        return $this->repo->GetByEmail($username);
    }

    public function UserEmailExists(string $email): bool {
        return $this->repo->UserEmailExists($email);
    }

    public function UpsertUser(User $user): ResponseInfo {
        if (isset($user->Uid) && $user->Uid > 0) {
            return $this->repo->Update($user);
        } else {
            return $this->repo->Insert($user);
        }
    }
}
?>