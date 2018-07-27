User Provider for CAS

Allow use only UID (returned by CasBundle) for application Symfony2, Symfony3 and Symfony4
(uid is the id user returned by jasig cas sso server and by the l3-team/CasBundle (repository github) or l3/cas-bundle (repository packagist))

Installation of the Bundle
---
Simple add this line in the require in your composer.json :
```
"l3/uid-user-bundle": "~1.0"
```
Launch the command **composer update** to install the package.

For Symfony 2 and 3 : add the Bundle in the AppKernel.php file.
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

For Symfony 4 :
Verify if the line are present in config/bundles.php file (if not present, just add the line) :
```
# config/bundles.php
...
L3\Bundle\UidUserBundle\L3UidUserBundle::class => ['all' => true],
...
```

Configuration of the bundle
---

For Symfony 2 and 3 : in the firewall of your application, use the Bundle :
```
# app/config/security.yml
security:
    providers:
        uid:
            id: uid_user_provider
```

For Symfony 4 : in the firewall of your application, use the Bundle :
```
# config/packages/security.yaml
security:
    providers:
        uid:
            id: uid_user_provider
```
