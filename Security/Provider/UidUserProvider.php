<?php

namespace L3\Bundle\UidUserBundle\Security\Provider;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\UserEvents;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Mautic\UserBundle\Security\Provider\UserProvider;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Entity\PermissionRepository;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use L3\Bundle\UidUserBundle\Entity\UidDirectory;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class UidUserProvider extends UserProvider implements UserProviderInterface {

    private $directory;

    private $directory_user;

    private $directory_user_permissions;

    private $username;

    private $uid;

    private $entityManager;

    private $mauticHelperPath;

    private $modelFactory;

    public function __construct(
        UserRepository $userRepository,
        PermissionRepository $permissionRepository,
        Session $session,
        EventDispatcherInterface $dispatcher,
        EncoderFactory $encoder,
        UidDirectory $directory,
        $entityManager,
        $mauticHelperPath,
        $modelFactory
    ) {
      $this->directory = $directory;
      $this->mauticHelperPath = $mauticHelperPath;
      $this->modelFactory = $modelFactory;
      parent::__construct($userRepository, $permissionRepository, $session, $dispatcher, $encoder);
      $this->entityManager = $entityManager;
    }

    public function loadUserByUsername($username) {
      $user = new User(false);
      $this->uid = $username;
      $this->username = $username;
      $user->setUsername($username);
      if (!$user->getUsername()) {
            throw new AuthenticationException('mautic.integration.sso.error.no_username');
        }
      $user->setFirstName($this->getFirstName());
      $user->setLastName($this->getLastName());
      if (!$user->getFirstName() || !$user->getLastName()) {
          throw new AuthenticationException('mautic.integration.sso.error.no_name');
      }
      $user->setEmail($this->getEmail());
      if (!$user->getEmail()) {
          throw new AuthenticationException('mautic.integration.sso.error.no_email');
      }
      $user = $this->findUser($user);
      $roles = array( /* relación permiso en el directorio -> rol en mautic */
        "gestionar-sistema" => 1,
        "enviar-boletines" => 3
      );
      $actions = $this->directory->get_actions($username);
      if (!$actions || empty($actions)) {
        $message = sprintf(
            'Unable to find an actions object identified by "%s".',
            $username
        );
        throw new AuthenticationException($message);
      }
      $authorized = false;
      $remove_roles = [];
      foreach ($actions as $value) {
        if (array_key_exists($value, $roles)) {
          if ($value == "gestionar-sistema") {
            $value = "Administrator";
          }
          $entity_rol = $this->entityManager->getRepository('MauticUserBundle:Role')->findOneBy(["name" =>$value]);
          $user->setRole($entity_rol);
          $authorized = true;
        }

      }
      
      if (!$user->getRole()) {
        throw new AuthenticationException('mautic.integration.sso.error.no_role');
      }
      if (in_array("gestionar-sistema", $actions)) {
        $user->getRole()->setIsAdmin(1);
      } else {
        $user->getRole()->setIsAdmin(0);
      }
      if (!$authorized) {
        throw new AuthenticationException('mautic.integration.sso.error.no_role');
      }      
      $user = $this->saveUser($user);
      if (empty($user)) {
            $message = sprintf(
                'Unable to find an active admin MauticUserBundle:User object identified by "%s".',
                $username
            );
            throw new UsernameNotFoundException($message, 0);
        }

        //load permissions
        $directory_permissions = $this->get_directory_permissions();
        if ($user->getId()) {
            $permissions = $this->permissionRepository->getPermissionsByRole($user->getRole());
            $permissions["territorios"] = $directory_permissions;
            $user->setActivePermissions($permissions);
        }

        return $user;
    }

    /**
     * Create/update user from authentication plugins.
     *
     * @param User      $user
     * @param bool|true $createIfNotExists
     *
     * @return User
     *
     * @throws BadCredentialsException
     */
    public function saveUser(User $user, $createIfNotExists = true)
    {
        $isNew = !$user->getId();

        if ($isNew) {
            if (!$createIfNotExists) {
                throw new BadCredentialsException();
            }
        }

        // Validation for User objects returned by a plugin
        if (!$user->getRole()) {
            throw new AuthenticationException('mautic.integration.sso.error.no_role');
        }

        if (!$user->getUsername()) {
            throw new AuthenticationException('mautic.integration.sso.error.no_username');
        }

        if (!$user->getEmail()) {
            throw new AuthenticationException('mautic.integration.sso.error.no_email');
        }

        if (!$user->getFirstName() || !$user->getLastName()) {
            throw new AuthenticationException('mautic.integration.sso.error.no_name');
        }

        // Check for plain password
        $plainPassword = $user->getPlainPassword();
        if ($plainPassword) {
            // Encode plain text
            $user->setPassword(
                $this->encoder->getEncoder($user)->encodePassword($plainPassword, $user->getSalt())
            );
        } elseif (!$password = $user->getPassword()) {
            // Generate and encode a random password
            $user->setPassword(
                $this->encoder->getEncoder($user)->encodePassword(EncryptionHelper::generateKey(), $user->getSalt())
            );
        }

        $event = new UserEvent($user, $isNew);

        if ($this->dispatcher->hasListeners(UserEvents::USER_PRE_SAVE)) {
            $event = $this->dispatcher->dispatch(UserEvents::USER_PRE_SAVE, $event);
        }
       
        $user->setLocale('es');
        $this->userRepository->saveEntity($user);
        $this->AssignDashboard($user);

        if ($this->dispatcher->hasListeners(UserEvents::USER_POST_SAVE)) {
            $this->dispatcher->dispatch(UserEvents::USER_POST_SAVE, $event);
        }

        return $user;
    }

    private function AssignDashboard($user){
      $file = 'global.withoutpermissions';
      $parts = explode('.', $file);
      $type  = array_shift($parts);
      $name  = implode('.', $parts);
      $dir  = $this->mauticHelperPath->getSystemPath("dashboard.$type");
      $path = $dir.'/'.$name.'.json';
      if (!file_exists($path) || !is_readable($path)) {
          return;
      }
      $widgets = json_decode(file_get_contents($path), true);
      if (isset($widgets['widgets'])) {
          $widgets = $widgets['widgets'];
      }

      if ($widgets) {
          /** @var \Mautic\DashboardBundle\Model\DashboardModel $model */
          $model = $this->modelFactory->getModel('dashboard');
          $model->clearDashboardCache();
          $currentWidgets = $model->getWidgets();
          if (count($currentWidgets)) {
              foreach ($currentWidgets as $widget) {
                  $model->deleteEntity($widget);
              }
          }
          $filter = $model->getDefaultFilter();
          foreach ($widgets as $widget) {
              $widget = $model->populateWidgetEntity($widget, $filter);
              $model->saveEntity($widget);
          }
      }
    }

    /**
     * @param User $user
     *
     * @return User
     */
    public function findUser(User $user)
    {
        try {
            // Try by username
            $user = parent::loadUserByUsername($user->getUsername());

            return $user;
        } catch (UsernameNotFoundException $exception) {
            // Try by email
            try {
                $user = parent::loadUserByUsername($user->getEmail());

                return $user;
            } catch (UsernameNotFoundException $exception) {
              return $user;
            }
        }

        return $user;
    }

    public function do_query($filtered, $model, $query,$scope=NULL, $scope_args=NULL) {
      $user = $filtered ? $this->username : $this->directory->get_system_user();
      return $this->directory->get_data($user,$model,$location=NULL,$action="null",$object="null",$query=$query,$application_id=NULL,$scope=$scope,$scope_args=$scope_args);
    }

    function get_directory_permissions(){
      $pip = $this->get_id_persona();
      $cacheKey = 'user-permisions-' . $this->username;
      $cache = $this->directory->getCache();
      $cache_item = $cache->getItem($cacheKey);
      if ($cache_item->isHit() && !empty($cache_item->get()) && 1==2) { //desactivamos la cache temporalmente. volver a activar.
        $this->directory_user_permissions = $cache_item->get();
      } else {
        $municipio = $this->directory->get_data($this->username,"municipio",$location=NULL,"enviar","boletines",NULL,NULL,NULL,NULL);
        $ids = array_map(function($localizacion){
          return $localizacion->id;
        }, $municipio);
        /*$distrito = $this->directory->get_data($this->username,"distrito",$location=NULL,"enviar","boletines",NULL,NULL,NULL,NULL);
        $ids = array_merge($ids, array_map(function($localizacion){
          return $localizacion->id;
        }, $distrito));*/
        $provincia = $this->directory->get_data($this->username,"provincia",$location=NULL,"enviar","boletines",NULL,NULL,NULL,NULL);
        $ids = array_merge($ids, array_map(function($localizacion){
          return $localizacion->id;
        }, $provincia));
        $isla = $this->directory->get_data($this->username,"isla",$location=NULL,"enviar","boletines",NULL,NULL,NULL,NULL);
        $ids = array_merge($ids, array_map(function($localizacion){
          return $localizacion->id;
        }, $isla));
        $comunidad = $this->directory->get_data($this->username,"comunidad",$location=NULL,"enviar","boletines",NULL,NULL,NULL,NULL);
        $ids = array_merge($ids, array_map(function($localizacion){
          return $localizacion->id;
        }, $comunidad));
        $this->directory_user_permissions = $ids;
        $cache_item->set($this->directory_user_permissions);
        $cache->save($cache_item);
      }
      
      return [
        0 => "ES-MD-M-092"
      ]; //temporal
      return $this->directory_user_permissions;
    }

    private function get_directory_user(){
      if (!isset($this->directory_user)) {
        $pip = $this->get_id_persona();
        $cacheKey = 'user-' . $pip;
        $cache = $this->directory->getCache();
        $cache_item = $cache->getItem($cacheKey);
        if ($cache_item->isHit() && !empty($cache_item->get())) {
          $this->directory_user = $cache_item->get();
        } else {
          //$this->directory_user = $this->do_query(false, "personas", ["id_eq" => $pip])[0];
          $users = $this->directory->get_data($this->username,"personas",$location=NULL,$action=NULL,$object=NULL,["id_eq" => $pip],NULL,NULL,NULL);
          if (!isset($users[0]) || empty($users[0])) {
            return null;
          }
          $this->directory_user = $users[0];
          if (isset($this->directory_user->nombre) || isset($this->directory_user->apellidos)) {
            $cache_item->set($this->directory_user);
            $cache->save($cache_item);
           }
        }
      }
      return $this->directory_user;
    }

    private function get_id_persona() {
      if ($this->uid[0]=="u" && is_numeric(substr($this->uid,1))){
        return intval(substr($this->uid,1))-11000;
      }
      return null;
    }

    /*
     * En dessous: getter et setter automatiquement généré par OpenLdapObject => Ne pas modifier si possible
     */

    private function getUid() {
        return $this->uid;
    }

    private function setUid($value) {
        $this->uid = $value;
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

    public function refreshUser(UserInterface $user) {
      
        if(!$user instanceof User) {
          return null;
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }
        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class) {
        return $class === 'Mautic\UserBundle\Entity\User';
    }
} 
