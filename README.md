Fournisseur d'identité depuis le CAS UDL

Permet d'utiliser uniquement CAS UDL comme fournisseur d'utilisateur pour obtenir que le UID dans une application Symfony2

Installation du Bundle.
---
L'installation du Bundle se fait via packagist (et donc composer.json). Vérifier que le serveur packagist de Lille3 et bien configuré par la présence du bloc suivant dans votre fichier composer.json :
```
    "repositories": [{
        "type": "composer",
        "url": "https://packagist.univ-lille3.fr/"
    }]
```
Puis vous pouvez installer facilement le Bundle en ajoutant cette ligne dans la partie require :
```
"l3/uid-user-bundle": "~1.0"
```
Lancer la commande **composer update** pour installer le package puis il faut ensuite ajouter le Bundle à AppKernel.php
```
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new L3\Bundle\UidUserBundle\L3UidUserBundle(),
        );

        // ...
    }

    // ...
}
```

Configuration du bundle
---
Dans le pare-feu pour utiliser l'user provider du Bundle :
```
# app/config/security.yml
security:
    providers:
            ldap:
                id: uid_user_provider
```
