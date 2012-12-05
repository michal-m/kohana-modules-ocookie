<?php defined('SYSPATH') or die('No direct script access.');

/**
 * OCookie class.
 *
 * @package    OCookie
 * @author     Kohana Team, Michał Musiał
 * @copyright  (c) 2008-2011 Kohana Team, modifications (c) 2012 Michał Musiał
 * @license    http://kohanaframework.org/license
 */
class OCookie
{
    /**
     * @var  array  OCookie instances
     */
    public static $instances = array();

    /**
     * Creates a singleton OCookie of a given name.
     *
     *     $cookie = OCookie::instance('my_cookie');
     *
     * @param   string   name of a cookie
     * @param   array    configuration
     * @return  OCookie
     */
    public static function instance($name, array $config = NULL)
    {
        if ( ! isset(OCookie::$instances[$name]))
        {
            // Create a new cookie instance
            OCookie::$instances[$name] = new OCookie($name, $config);
        }

        return OCookie::$instances[$name];
    }

	/**
	 * @var  string  Cookie name
	 */
	protected $_name;

	/**
	 * @var  integer  Number of seconds before the cookie expires
	 */
	protected $_lifetime = 0;

	/**
	 * @var  string  Restrict the path that the cookie is available to
	 */
	protected $_path = '';

	/**
	 * @var  string  Restrict the domain that the cookie is available to
	 */
	protected $_domain;

	/**
	 * @var  boolean  Only transmit cookies over secure connections
	 */
	protected $_secure = FALSE;

	/**
	 * @var  boolean  Only transmit cookies over HTTP, disabling Javascript access
	 */
	protected $_httponly = TRUE;

    /**
     * @var  boolean  Encrypt cookie
     */
    protected $_encrypted = FALSE;

    /**
     * @var  string   Cookie value
     */
    protected $_value;

    /**
     * @var  boolean  Is cookie loaded
     */
    protected $_loaded = FALSE;

    /**
     * Initiates the cookie.
     *
     * [!!] Cookies can only be created using the [OCookie::instance] method.
     *
     * @param   string  name
     * @param   array   configuration
     * @return  OCookie
     * @uses    Kohana::$config
     */
    protected function __construct($name, array $config = NULL)
    {
        // Cookie name
        $this->_name = (string) $name;

        // Load configuration
        if ($config === NULL)
        {
            $config = Kohana::$config->load('ocookie')->get($name);

            if ($config === NULL)
            {
                $config = Kohana::$config->load('ocookie')->get('default');
            }
        }

        if (isset($config['lifetime']))
        {
            // Cookie lifetime
            $this->_lifetime = (int) $config['lifetime'];
        }

        if (isset($config['path']))
        {
            $this->_path = (string) $config['path'];
        }

        if (isset($config['domain']))
        {
            $this->_domain = (string) $config['domain'];
        }
        else
        {
            $this->_domain = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
        }

        if (isset($config['secure']))
        {
            $this->_secure = (bool) $config['secure'];
        }

        if (isset($config['httponly']))
        {
            $this->_httponly = (bool) $config['httponly'];
        }

        if (isset($config['encrypted']))
        {
            if ($config['encrypted'] === TRUE)
            {
                // Use the default Encrypt instance
                $config['encrypted'] = 'default';
            }

            // Enable or disable encryption of data
            $this->_encrypted = $config['encrypted'];
        }

        // Read the cookie
        $this->_read();
    }

	/**
	 * Gets the value of the cookie.
	 *
	 *     // Get the cookie value, or use "blue" if the cookie does not exist
	 *     $cookie->get('blue');
	 *
	 * @param   mixed   default value to return
	 * @return  string
	 */
	public function get($default = NULL)
	{
		return isset($this->_value) ? $this->_value : $default;
	}

	/**
	 * Sets a signed cookie. Note that all cookie values must be strings and no
	 * automatic serialization will be performed!
	 *
	 *     // Set the "theme" cookie
     *     $cookie->set('red');
	 *
	 * @param   string   value of cookie
	 * @param   integer  lifetime in seconds
	 * @return  boolean
	 * @uses    Cookie::salt
	 */
	public function set($value, $lifetime = NULL)
	{
        $this->_value = $value;

		if ($lifetime === NULL)
		{
			// Use the default lifetime
			$lifetime = $this->_lifetime;
		}

		if ($lifetime !== 0)
		{
			// The lifetime is expected to be a UNIX timestamp
			$lifetime += time();
		}

		// Add the salt to the cookie value
		$value = Cookie::salt($this->_name, $value).'~'.$value;

		return setcookie($this->_name, $value, $this->_lifetime, $this->_path, $this->_domain, $this->_secure, $this->_httponly);
	}

	/**
	 * Deletes a cookie by making the value NULL and expiring it.
	 *
	 *     $cookie->delete();
	 *
	 * @param   string   cookie name
	 * @return  boolean
	 */
	public function delete()
	{
		// Remove the cookie
		unset($_COOKIE[$this->_name]);

		// Nullify the cookie and make it expire
		return setcookie($this->_name, NULL, -86400, $this->_path, $this->_domain, $this->_secure, $this->_httponly);
	}

    /**
     * Returns `$this->_loaded` value which is set on true when successfully
     * loaded a cookie.
     *
     * @return boolean
     */
    public function loaded()
    {
        return $this->_loaded;
    }

    /**
     * Reads a signed cookie's value. Cookies without signatures will not be
     * read. If the cookie signature is present, but invalid, the cookie will be
     * deleted.
     *
     * @return void
     * @uses Cookie::salt
     */
    protected function _read()
    {
		if ( ! isset($_COOKIE[$this->_name]))
		{
			// The cookie does not exist
			$this->_value = NULL;
            return;
		}

		// Get the cookie value
		$cookie = $_COOKIE[$this->_name];

		// Find the position of the split between salt and contents
		$split = strlen(Cookie::salt($this->_name, NULL));

		if (isset($cookie[$split]) AND $cookie[$split] === '~')
		{
			// Separate the salt and the value
			list ($hash, $value) = explode('~', $cookie, 2);

			if (Cookie::salt($this->_name, $value) === $hash)
			{
				// Cookie signature is valid
				$this->_value = $value;
                $this->_loaded = TRUE;
                return;
			}

			// The cookie signature is invalid, delete it
			$this->delete();
		}

		$this->_value = NULL;
    }
}
