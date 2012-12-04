<?php defined('SYSPATH') or die('No direct script access.');

/**
 * The array below contains cookie configuration. Array keys are the cookie
 * names.
 */
return array(
    /**
     * The following options are available:
     *
     * integer  lifetime    session lifetime in seconds
     * string   path        cookie path
     * string   domain      cookie domain
     * boolean  secure      whether the cookie is to be served only over HTTPS
     * boolean  encrypted   whether to encrypt session data
     */
    'cookie_name' => array(
        'lifetime'      => 43200,
        'path'          => '',
        'domain'        => '',
        'secure'        => FALSE,
        'httponly'      => TRUE,
    ),
);
