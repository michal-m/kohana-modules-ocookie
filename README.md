Kohana Objective Cookie Module
==============================

By default Kohana supports Cookie manipulation via it's static Kohana_Cookie
class. This makes it hard to work with cookies if your application requires to
support more than one, each with different settings. This module solves this
problem by giving you access to cookies served as objects, rather than static
methods, where each one is configurable on its own.

## Features

- Fully Object Oriented cookies - no longer using static Kohana_Cookie helper class.
- Much more precise control over cookies (ability to precisely set each cookie's settings rather than using global ones)
- Optional automatic encryption of cookie values
- Optional automatic serialization of cookie values (no longer have to worry about making sure they're in string format)

## Installation

1. Copy and paste files and folders to `MODPATH/ocookie`.
2. (Optional) Copy `MODPATH/ocookie/config/sessions.php` to your `APPPATH/config` folder.
3. Add this entry under `Kohana::modules` array in `APPPATH/bootstrap.php`:

```php
'ocookie'	=> MODPATH.'ocookie',	 // Objective Cookie
```

## Configuration

You can configure your cookies in your `APPPATH/config/sessions.php` file.
The configuration is very basic and very similar to what properties
Kohana_Cookie static class has:

```php
/**
 * The array below contains cookie configuration. Array keys are the cookie
 * names.
 */
return array(
    /**
     * The following options are available:
     *
     * integer  lifetime    cookie lifetime in seconds
     * string   path        cookie path
     * string   domain      cookie domain
     * boolean  secure      whether the cookie is to be served only over HTTPS
     * boolean  httponly    whether the cookie is to be available to JS or not
     * boolean  serialize   whether to automatically serialize and unserialize cookie value
     * boolean  encrypted   whether to encrypt session data (overrides serialize setting)
     */
    'default' => array(
        'lifetime'      => Cookie::$expiration,
        'path'          => Cookie::$path,
        'domain'        => Cookie::$domain,
        'secure'        => Cookie::$secure,
        'httponly'      => Cookie::$httponly,
        'serialize'     => FALSE,
        'encrypted'     => FALSE,   // or encryption setting name
    ),
);
```

## Usage examples


## Acknowledgements

The code of this module is heavily based on both Kohana_Cookie and Kohana_Session
classes.
