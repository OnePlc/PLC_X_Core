# Introduction

oneplace-core is the Core layer shipped with onePlace X and above, and focuses on performance and stability.

The Core layer is built on top of the following components:
 - laminas-mvc - laminas-mvc provides basic mvc-framework
 
Within the Core layer, several sub-components are exposed:
- `Application\Controller\CoreController`, a set of abstract "controller" classes with basic
  responsibilities such database connection, performance logging and much more
- `Application\Model\CoreEntityModel`, provides a solid base for entity Models for your own modules
  like dynamic fields, plugins and more
- `Application\Model\CoreEntityTable`, provides toolset for your table models in your own modules
  like dynamic fields, plugins and more
  
## Basic Application Structure
The basic application structure follows:

```bash
application_root/
    config/
        application.config.php
        autoload/
            global.php
            local.php
            // etc.
    data/
    module/
    vendor/
    public/
        .htaccess
        index.php
        vendor/
    init_autoloader.php
```

The `public/index.php` script marshals all user requests to your website,
retrieving an array of configuration from `config/application.config.php`. On
return, it `run()`s the `Application`, processing the request and returning a
response to the user.

The `config` directory as described above contains configuration used by
laminas-modulemanager to load modules and merge configuration (e.g., database
configuration and credentials); we will detail this more later.

The `vendor` sub-directory should contain any third-party modules or libraries
on which your application depends. This might include Laminas, custom
libraries from your organization, or other third-party libraries from other
projects. Libraries and modules placed in the `vendor` sub-directory should not
be modified from their original, distributed state.  Typically, this directory
will be managed by [Composer](https://getcomposer.org).

The `vendor` sub-directory in `public` does the same for any third-party frontend
libraries (javascript/css/fonts/themes). This might include jQuery-ui, bootstrap.
Libraries and modules placed in the `vendor` sub-directory should not
be modified from their original, distributed state.

Finally, the `module` directory will contain one or more modules delivering your
application's functionality.

Let's now turn to modules, as they are the basic units of a web application.

## Basic Module Structure

A module may contain anything: PHP code, including MVC functionality; library
code; view scripts; and/or or public assets such as images, CSS, and JavaScript.
The only requirement &mdash; and even this is optional &mdash; is that a module
acts as a PHP namespace and that it contains a `Module` class under that
namespace.  This class is eventually consumed by laminas-modulemanager to perform a
number of tasks.

The recommended module structure follows:

```bash
module_root<named-after-module-namespace>/
    Module.php
    autoload_classmap.php
    autoload_function.php
    autoload_register.php
    config/
        module.config.php
    public/
        images/
        css/
        js/
    src/
        <module_namespace>/
            <code files>
    test/
        phpunit.xml
        bootstrap.php
        <module_namespace>/
            <test code files>
    view/
        <dir-named-after-module-namespace>/
            <dir-named-after-a-controller>/
                <.phtml files>
```

Since a module acts as a namespace, the module root directory should be that
namespace. This namespace could also include a vendor prefix of sorts. As an
example a module centered around "User" functionality delivered by onePlace might be
named "OnePlaceUser", and this is also what the module root directory will be named.

> ### Source and test code organization
>
> The above details a [PSR-0](http://www.php-fig.org/psr/psr-0/) structure for
> the source and test code directories. You can also use
> [PSR-4](http://www.php-fig.org/psr/psr-4/) so long as you have setup
> autoloading correctly to do so.

The `Module.php` file directly under the module root directory will be in the
module namespace shown below.

```php
namespace OnePlaceUser;

class Module
{
}
```


## Conclusion
oneplace-core is incredibly flexible, offering an opt-in, easy to create modular
infrastructure, as well as the ability to craft your own application workflows
thanks to dynamic fields, auto-generated forms & index-tables, action based permission
system and much more.