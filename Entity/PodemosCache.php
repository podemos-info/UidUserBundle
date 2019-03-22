<?php
/**
 * Podemos Mautic Auth cache
 *
 * @author Joao Maria Arranz
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace L3\Bundle\UidUserBundle\Entity;

final class PodemosCache
{
  /** @var \OCP\ICache|null */
  private $cache;

  /**
   * Call this method to get singleton
   *
   * @return PodemosCache
   */
  public static function Instance()
  {
    static $inst = null;
    if ($inst === null) {
      $inst = new PodemosCache();
    }
    return $inst;
  }

  private function __construct()
  {
    $memcache = \OC::$server->getMemCacheFactory();
    if ($memcache->isAvailable()) {
      $this->cache = $memcache->create();
    }
  }

  /**
   * @param string $key
   * @return mixed|null
   */
  public function getFromCache($key)
  {
    if (is_null($this->cache) || !$this->isCached($key)) {
      return null;
    }
    $key = $this->getCacheKey($key);

    return json_decode(base64_decode($this->cache->get($key)));
  }

  /**
   * @param string $key
   * @return bool
   */
  public function isCached($key)
  {
    if (is_null($this->cache)) {
      return false;
    }
    $key = $this->getCacheKey($key);
    return $this->cache->hasKey($key);
  }

  /**
   * @param string $key
   * @param mixed $value
   */
  public function writeToCache($key, $value)
  {
    if (is_null($this->cache)) {
      return;
    }
    $key   = $this->getCacheKey($key);
    $value = base64_encode(json_encode($value));
    $this->cache->set($key, $value, '2592000'); //3dias
  }

  /**
   * @param string|null $key
   * @return string
   */
  private function getCacheKey($key)
  {
    $prefix = 'Podemos-Cache-';
    if (is_null($key)) {
      return $prefix;
    }
    return $prefix . md5($key);
  }

  public function clearCache()
  {
    if (is_null($this->cache)) {
      return;
    }
    $this->cache->clear($this->getCacheKey(null));
  }
}
