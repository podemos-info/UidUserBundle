<?php

namespace L3\Bundle\UidUserBundle\Services;

use L3\Bundle\UidUserBundle\Entity\UidUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UidUserProvider implements UserProviderInterface {

    public function __construct() {}

    public function loadUserByUsername($username) {
        $user = $user = new UidUser();
        $user->setUid($username);
	$user->updateRoles();        
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
