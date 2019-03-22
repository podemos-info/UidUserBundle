<?php

namespace L3\Bundle\UidUserBundle\Entity;

use Mautic\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\UserInterface;

class UidUser extends User implements UserInterface
{

  protected $uid;
  private $directory_user;
  private $directory;
  private $roles = array();

  /**
   * User constructor.
   *
   * @param bool $isGuest
   */
  public function __construct($isGuest = false, $directory)
  {
    $this->directory = $directory;
    parent::__construct($isGuest);
  }

  public function do_query($filtered, $model, $query, $scope = null, $scope_args = null)
  {
    $user = $filtered ? $this->getUsername() : $this->directory->get_system_user();
    return $this->directory->get_data(
      $user, 
      $model, 
      $location = null, 
      $action = "ver", 
      $object = "intranet", 
      $query = 
      $query, 
      $application_id = null, 
      $scope = $scope, 
      $scope_args = $scope_args
    );
  }

  private function get_directory_user()
  {
    if (!isset($this->directory_user)) {
      $pip        = $this->get_id_persona();
      $cacheKey   = 'user-' . $pip;
      $cache      = $this->directory->getCache();
      $cache_item = $cache->getItem($cacheKey);
      if ($cache_item->isHit()) {
        $this->directory_user = $cache_item->get();
      } else {
        $this->directory_user = $this->do_query(false, "personas", ["id_eq" => $pip])[0];
        if (isset($this->directory_user->nombre) || isset($this->directory_user->apallidos)) {
          $cache_item->set($this->directory_user);
          $cache->save($cache_item);
        }
      }
    }
    return $this->directory_user;
  }

  private function get_id_persona()
  {
    if ($this->uid[0] == "u" && is_numeric(substr($this->uid, 1))) {
      return intval(substr($this->uid, 1)) - 11000;
    }
    return null;
  }

  /**
   * Determines if user is admin.
   *
   * @return bool
   */
  public function isAdmin()
  {
    return true;
  }

  public function updateRoles(array $rolesConfig = array())
  {
    $this->roles = $rolesConfig;
    return;
  }

  /**
   * Returns the roles granted to the user.
   *
   * @return Role[] The user roles
   */
  public function getRoles()
  {
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
  public function getPassword()
  {
    return null;
  }

  /**
   * Returns the salt that was originally used to encode the password.
   *
   * This can return null if the password was not encoded using a salt.
   *
   * @return string|null The salt
   */
  public function getSalt()
  {
    return null;
  }

  /**
   * Returns the username used to authenticate the user.
   *
   * @return string The username
   */
  public function getUsername()
  {
    return $this->getUid();
  }

  /**
   * Removes sensitive data from the user.
   *
   * This is important if, at any given point, sensitive information like
   * the plain-text password is stored on this object.
   */
  public function eraseCredentials()
  {}

  public function equals(UserInterface $user)
  {
    if (!$user instanceof UidUser) {
      return false;
    }

    if ($user->getUid() !== $this->getUid()) {
      return false;
    }

    return true;
  }

  /*
   * En dessous: getter et setter automatiquement généré par OpenLdapObject => Ne pas modifier si possible
   */

  public function getUid()
  {
    return $this->uid;
  }

  public function setUid($value)
  {
    $this->uid = $value;
    return $this;
  }

  /**
   * Set firstName.
   *
   * @param string $firstName
   *
   * @return User
   */
  public function setFirstName($firstName)
  {
    //Do nothoing, not allowd changes in directory
    return $this;
  }

  /**
   * Get firstName.
   *
   * @return string
   */
  public function getFirstName()
  {
    $directory_user = $this->get_directory_user();
    if (isset($directory_user->nombre)) {
      return $directory_user->nombre;
    }
    return false;
  }

  /**
   * Set lastName.
   *
   * @param string $lastName
   *
   * @return User
   */
  public function setLastName($lastName)
  {
    //Do nothoing, not allowd changes in directory
    return $this;
  }

  /**
   * Get lastName.
   *
   * @return string
   */
  public function getLastName()
  {
    $directory_user = $this->get_directory_user();
    if (isset($directory_user->apellidos)) {
      return $directory_user->apellidos;
    }
    return false;
  }

  /**
   * Get full name.
   *
   * @param bool $lastFirst
   *
   * @return string
   */
  public function getName($lastFirst = false)
  {
    return ($lastFirst) ? $this->getLastName() . ', ' . $this->getFirstName() : $this->getFirstName() . ' ' . $this->getLastName();
  }

  /**
   * Set email.
   *
   * @param string $email
   *
   * @return User
   */
  public function setEmail($email)
  {
    //Do nothoing, not allowd changes in directory
    return $this;
  }

  /**
   * Get email.
   *
   * @return string || null
   */
  public function getEmail()
  {
    $directory_user = $this->get_directory_user();
    if (isset($directory_user->email)) {
      return $directory_user->email;
    }
    return false;
  }
}
