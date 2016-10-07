<?php

namespace L3\Bundle\UidUserBundle\Entity;

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\UserInterface;


class UidUser implements UserInterface {

    protected $uid;
    private $roles = array();

    public function updateRoles() {        
        $this->roles = array('ROLE_USER');
        return; 
    }

    /**
     * Returns the roles granted to the user.
     *
     * @return Role[] The user roles
     */
    public function getRoles() {
        return $this->roles;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword() {
        return null;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt() {
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername() {
        return $this->getUid();
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials() {}

    public function equals(UserInterface $user) {
        if(!$user instanceof UidUser) {
            return false;
        }

        if($user->getUid() !== $this->getUid()) {
            return false;
        }

        return true;
    }

    /*
     * En dessous: getter et setter automatiquement généré par OpenLdapObject => Ne pas modifier si possible
     */

    public function getUid() {
        return $this->uid;
    }

    public function setUid($value) {
        $this->uid = $value;
        return $this;
    }    

}
?>
