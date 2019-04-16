<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace L3\Bundle\UidUserBundle\Menu;

use Mautic\CoreBundle\Menu\MenuHelper as MauticMenuHelper;

/**
 * Class MenuHelper.
 */
class MenuHelper extendS MauticMenuHelper
{

    /**
     * @param $accessLevel
     *
     * @return bool
     */
    protected function handleAccessCheck($accessLevel)
    {
        switch ($accessLevel) {
            case 'admin':
            if (!$this->security->isAdmin()) { //THIS IS A PULL REQUEST WAITING FOR APROVAL.
              return false;
            }
            return $this->security->isAdmin();
            default:
                return $this->security->isGranted($accessLevel, 'MATCH_ONE');
        }
    }

    /**
     * Handle access check and other checks for menu items.
     *
     * @param array $menuItem
     *
     * @return bool Returns false if the item fails the access check or any other checks
     */
    protected function handleChecks(array $menuItem)
    {
      if (isset($menuItem["id"]) && $menuItem["id"] == "mautic_components_root") {
        $menuItem["access"][] = "asset:assets:viewown";
        $menuItem["access"][] = "asset:assets:viewother";
        $menuItem["access"][] = "dynamiccontent:dynamiccontents:viewown";
        $menuItem["access"][] = "dynamiccontent:dynamiccontents:viewother";
        $menuItem["access"][] = "form:forms:viewother";
        $menuItem["access"][] = "form:forms:viewown";
        $menuItem["access"][] = "page:pages:viewown";
        $menuItem["access"][] = "page:pages:viewother";
      }
      
        if (isset($menuItem['access']) && $this->handleAccessCheck($menuItem['access']) === false) {
            return false;
        }

        if (isset($menuItem['checks']) && is_array($menuItem['checks'])) {
            foreach ($menuItem['checks'] as $checkGroup => $checkConfig) {
                $checkMethod = 'handle'.ucfirst($checkGroup).'Checks';

                if (!method_exists($this, $checkMethod)) {
                    continue;
                }

                foreach ($checkConfig as $name => $value) {
                    if ($this->$checkMethod($name, $value) === false) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
