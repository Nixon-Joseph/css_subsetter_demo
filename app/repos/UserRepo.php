<?php
class UserRepo extends \devpirates\MVC\Base\Repo {
    public function __construct() {
        parent::__construct("User");
    }

    public function GetAllUsers() : array {
        return $this->_getAll();
    }

    public function UserEmailExists(string $email): bool {
        try {
            $statement = $this->db->prepare("SELECT COUNT(`Uid`) FROM $this->table WHERE `Email`=?");
            $statement->execute([$email]);
            return $statement->fetchColumn() > 0;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function UsernameExists(string $username): bool {
        try {
            $statement = $this->db->prepare("SELECT COUNT(`Uid`) FROM $this->table WHERE `Username`=?");
            $statement->execute([$username]);
            return $statement->fetchColumn() > 0;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function GetByUsername(string $username): User {
        try {
            $statement = $this->db->prepare("SELECT $this->columnString FROM $this->table WHERE `Username`=? LIMIT 1");
            $statement->setFetchMode(PDO::FETCH_CLASS, $this->className);
            $statement->execute([$username]);
            return $this->fixPDOMapping($statement->fetch());
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function GetByEmail(string $email): User {
        try {
            $statement = $this->db->prepare("SELECT $this->columnString FROM $this->table WHERE `Email`=? LIMIT 1");
            $statement->setFetchMode(PDO::FETCH_CLASS, $this->className);
            $statement->execute([$email]);
            return $this->fixPDOMapping($statement->fetch());
        } catch (\Throwable $th) {
            return null;
        }
    }

    private function fixPDOMapping(?User $user): ?User {
        if (isset($user)) {
            $user->Require2FA = isset($user->Require2FA) && $user->Require2FA !== '0';
        }
        return $user;
    }

    public function Insert(User $user): ResponseInfo {
        return $this->_insert($user);
    }

    public function Update(User $user): ResponseInfo {
        return $this->_update($user);
    }

    public function Delete(int $uid): ResponseInfo {
        return $this->_delete($uid);
    }
}

class User {
    /**
     * User Identifier
     *
     * @var int
     */
    public $Uid;
    /**
     * Username
     *
     * @var string
     */
    public $Username;
    /**
     * Email Address
     *
     * @var string
     */
    public $Email;
    /**
     * Password hash
     *
     * @var string
     */
    public $PasswordHash;
    /**
     * Password salt
     *
     * @var string
     */
    public $PasswordSalt;
    /**
     * Require 2FA authentication on login
     *
     * @var bool
     */
    public $Require2FA;
    /**
     * 2FA Secret
     *
     * @var string
     */
    public $Secret2FA;
}
?>