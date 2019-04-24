<?php

/*
 * @author      Joao Maria Arranz
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
return [
  'name'        => 'PUid user bundle',
  'description' => 'Pxxxxxxx',
  'version'     => '1.0',
  'author'      => 'Joao Maria Arranz',
  'services' => [
    'events' => [
      'mautic.user.subscriber' => [
        'class'     => L3\Bundle\UidUserBundle\EventListener\AssetsSubscriber::class,
      ],
    ],
  ]
];
