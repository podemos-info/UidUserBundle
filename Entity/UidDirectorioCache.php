<?php

/**
 * Podemos Mautic Auth cache
 *
 * @author Joao Maria Arranz
 * 
 */

namespace L3\Bundle\UidUserBundle\Entity;

class DirectorioCache
{
  public static $memcache = null;
  static $cache_tags;
  public static function init_cache()
  {
    if (self::$memcache == null) {
      self::$memcache = new Memcached;
      self::$memcache->addServer("localhost", 11211);
      self::$cache_tags = json_decode(file_get_contents(DIRECTORIO_API_SERVER . '/system/tags.json'), true);
    }
  }
  public static function check_cache($key)
  {
    $cache_data = self::$memcache->getMulti(["php_ts_$key", "php_query_$key", "php_tags_$key"]);
    if (isset($cache_data["php_ts_$key"]) && isset($cache_data["php_query_$key"]) && isset($cache_data["php_tags_$key"])) {
      $php_cache_tags = array_fill_keys(array_map(function ($x) {return "tag_$x";}, unserialize($cache_data["php_tags_$key"])), 1);
      if (is_array(self::$cache_tags) && is_array($php_cache_tags)) {
        $ts_system = max(array_map("intval", array_values(array_intersect_key(self::$cache_tags, $php_cache_tags))));
        if (intval($cache_data["php_ts_$key"]) > $ts_system) {
          return unserialize($cache_data["php_query_$key"]);
        }
      }
    }
    return null;
  }
  public static function save_cache($key, $cache_tags, $results)
  {
    $cache_tags = json_decode($cache_tags);
    if (count($cache_tags) > 0) {
      self::$memcache->setMulti(["php_ts_$key" => strval(intval(microtime(true) * 1000)),
        "php_query_$key"                         => serialize($results),
        "php_tags_$key"                          => serialize($cache_tags),
      ]);
      if (self::$memcache->getResultCode() != 0) {
        error_log("Memcached failed with erro code: " . self::$memcache->getResultCode());
      }

    }
  }
}
