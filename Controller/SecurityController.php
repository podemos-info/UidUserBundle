<?php

namespace L3\Bundle\UidUserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class SecurityController extends Controller {
    /**
     * @Security("has_role('ROLE_USER')")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $roles = array();
        return $this->render('L3UidUserBundle:Default:index.html.twig', array('user' => $this->getUser(), 'roles' => 'ROLE_USER'));
    }
}
