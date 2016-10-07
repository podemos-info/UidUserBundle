User Provider for CAS

Allow use only UID for application Symfony2

Installation of the Bundle
---
Simple add this line in the require in your composer.json :
```
"l3/uid-user-bundle": "~1.0"
```
Launch the command **composer update** to install the package and add the Bundle in the AppKernel.php file.
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

Configuration of the bundle
---
In the firewall of your application, use the Bundle :
```
# app/config/security.yml
security:
    providers:
            ldap:
                id: uid_user_provider
```
