<?php

namespace L3\Bundle\UidUserBundle\Services;

use L3\Bundle\UidUserBundle\Entity\UidUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Mautic\UserBundle\Security\Provider\UserProvider;

class UidUserProvider extends UserProvider implements UserProviderInterface {

    public function __construct() {}

    public function loadUserByUsername($username) {
        $user = new UidUser();
        $user->setUid($username);
        $roles = Array();
        if ($username === '__NO_USER__') {
            $roles = array('ROLE_ADMIN');
        } else {
            $roles = array('ROLE_ADMIN', 'ROLE_USER');
        }
        $user->updateRoles($roles);
        return $user;
    }

    public function refreshUser(UserInterface $user) {
        if(!$user instanceof UidUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUid());
    }

    public function supportsClass($class) {
        return $class === 'L3\Bundle\UidLdapBundle\Entity\UidUser';
    }
} 
