<?php

/*
 * @author      Joao Maria Arranz
 *
 */

namespace L3\Bundle\UidUserBundle\Entity;

use Symfony\Component\Cache\Adapter\MemcachedAdapter;

/**
 * UidUserRepository.
 */

class UidDirectory
{

  protected $cache;
  private $directorio_app_id;
  private $directorio_api_server;
  private $directorio_api_version;
  private $system_user;
  private $directory_object;

  public function __construct($directorio_app_id, $directorio_api_server, $directorio_api_version, $system_user, $directory_object)
  {
    $client                       = MemcachedAdapter::createConnection('memcached://127.0.0.1:11211');
    $this->cache                  = new MemcachedAdapter($client);
    $this->directorio_app_id      = $directorio_app_id;
    $this->directorio_api_server  = $directorio_api_server;
    $this->directorio_api_version = $directorio_api_version;
    $this->system_user            = $system_user;
    $this->directory_object       = $directory_object;
  }

  public function get_system_user()
  {
    return $this->system_user;
  }

  public function getCache()
  {
    return $this->cache;
  }

  public function has_permissions($user, $location = null, $action = null, $object = null, $application_id = null, &$metadata = null)
  {
    if ($application_id == null) {
      $application_id = $this->directorio_app_id;
    }
    $params = array('uid' => $user);
    if ($location != null) {
      $params["territorio"] = $location;
    }

    if ($action != null) {
      $params["accion"] = $action;
    }

    if ($object != null) {
      $params["objeto"] = $object;
    }

    if ($this->directorio_app_id != null) {
      $params["aplicacion"] = $this->directorio_app_id;
    }

    if ($application_id != null) {
      if ($application_id > 0) {
        $params["aplicacion"] = $application_id;
      } else {
        unset($params["aplicacion"]);
      }

    }
    $key = md5(serialize($params));

    $cache_data = $this->cache->getItem($key);
    if ($cache_data->isHit()) {
      return $cache_data->get();
    }
    require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/tcdent/php-restclient/restclient.php";
    $api    = new \RestClient(array('base_url' => $this->directorio_api_server, 'format' => "json", 'headers' => array()));
    $result = $api->get("api/v" . $this->directorio_api_version . "/puede", $params);
    if ($result->info->http_code == 200) {
      $metadata = array("usuario" => $result->headers->x_directorio_usuario);
      $results  = $result->decode_response();
      $cache_data->set($results);
      $this->cache->save($cache_data);
      return $results;
    } else {
      return null;
    }

  }
  public function get_data($user, $model, $location = null, $action = null, $object = null, $query = null, $application_id = null, $scope = null, $scope_args = null)
  {
    if ($application_id == null) {
      $application_id = $this->directorio_app_id;
    }
    $params = array('uid' => $user, 'modelo' => $model);
    if ($location != null) {
      $params["territorio"] = $location;
    }

    if ($action != null) {
      $params["accion"] = $action;
    }

    if ($object != null) {
      $params["objeto"] = $object;
    }

    if ($this->directorio_app_id != null) {
      $params["aplicacion"] = $this->directorio_app_id;
    }

    if ($application_id != null) {
      if ($application_id > 0) {
        $params["aplicacion"] = $application_id;
      } else {
        unset($params["aplicacion"]);
      }

    }
    if ($query != null) {
      foreach ($query as $key => $value) {
        $params["q[$key]"] = $value;
      }
    }

    if ($scope != null) {
      $params["scope"] = $scope;
      if ($scope_args != null) {
        $params["scope_args"] = implode(",", $scope_args);
      }

    }

    /* connect to memcached server */
    $key = md5(serialize($params));

    $cache_data = $this->cache->getItem($key);
    if ($cache_data->isHit() && !empty($cache_data->get()) && !empty($cache_data->get()->decoded_response)) {
      return $cache_data->get();
    }
    require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/tcdent/php-restclient/restclient.php";
    $api    = new \RestClient(array('base_url' => $this->directorio_api_server, 'format' => "json", 'headers' => array()));
    $result = $api->get("api/v" . $this->directorio_api_version . "/obtener", $params);
    if ($result->info->http_code == 200) {
      $results = $result->decode_response();
      $cache_data->set($results);
      //dump($result);
      $this->cache->save($cache_data);
      return $results;
    } else {
      //dump($result);
      return null;
    }
  }
  public function unimpersonate($user)
  {
    $params = array('uid' => $user);
    require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/tcdent/php-restclient/restclient.php";
    $api    = new \RestClient(array('base_url' => $this->directorio_api_server, 'format' => "json", 'headers' => array()));
    $result = $api->get("api/v" . $this->directorio_api_version . "/desimpersonar", $params);
    if ($result->info->http_code == 200) {
      return $result->decode_response();
    } else {
      return null;
    }

  }
  public function auth($user, $email, $pass)
  {
    $params = array('uid' => $user, 'email' => $email, 'clave' => $pass);
    require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/tcdent/php-restclient/restclient.php";
    $api    = new \RestClient(array('base_url' => $this->directorio_api_server, 'format' => "json", 'headers' => array()));
    $result = $api->post("api/v" . $this->directorio_api_version . "/autenticar", $params);
    if ($result->info->http_code == 201) {
      return $result->decode_response();
    } else {
      return null;
    }
  }

  public function get_object()
  {
    if ($this->directory_object == null) {
      $this->directory_object = str_replace(".", "_", $_SERVER["HTTP_HOST"]);
      if (substr($this->directory_object, -5) == "-test") {
        $this->directory_object = substr($object, 0, -5);
      }

    }
    return $this->directory_object;
  }

  public function get_actions($username)
  {
    $permissions = $this->get_data($username, "permisos", $location = null, $action = null, $object = null);
    if ($permissions) {
      return array_map(function ($permission) {return $permission->accion."-".$permission->objeto;}, $permissions);
    } else {
      return [];
    }

  }
}
