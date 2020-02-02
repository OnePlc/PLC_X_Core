# Installation

Using composer

```bash
$ composer create-project oneplace/oneplace-core my-app
```

After you successfully installed core, you may add 3rd party
dependencies for certain features (they are disabled if libs are not found)

the easiest way is to use yarn. if you dont have yarn, [get it here](https://legacy.yarnpkg.com/en/docs/install/#windows-stable)
you can also use npm if you like

```
$ cd my-app
$ yarn install
```
## support for environment variables in setup

if you are testing and often have to deploy the same oneplace system.
you can set the fields needed for setup in your servers environments
variable - so its automatically pre-filled.

For nginx (in your virtual host file)
```bash
location ~ \.php$ {
    # here some code maybe
    fastcgi_param PLCSETUPDBHOST "localhost";
    fastcgi_param PLCSETUPDBUSER "root";
    fastcgi_param PLCSETUPDBNAME "plcdemo";
    fastcgi_param PLCSETUPDBPASS "";
    fastcgi_param PLCSETUPADMINUSER "plcroot";
    fastcgi_param PLCSETUPADMINMAIL "admin@example.com";
    fastcgi_param PLCSETUPADMINPASS "1234";
    # insert params just above fastcgi_pass
    fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
}
```