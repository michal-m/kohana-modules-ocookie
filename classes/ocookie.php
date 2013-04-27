<?php defined('SYSPATH') or die('No direct script access.');

/**
 * oCookie class.
 *
 * @package    oCookie
 * @author     Kohana Team, Michał Musiał
 * @copyright  (c) 2008-2011 Kohana Team, modifications (c) 2012 Michał Musiał
 * @license    http://kohanaframework.org/license
 */
class oCookie
{
    /**
     * @var  array  oCookie instances
     */
    public static $instances = array();

    /**
     * Creates a singleton oCookie of a given name.
     *
     *     $cookie = oCookie::instance('my_cookie');
     *
     * @param   string   name of a cookie
     * @param   array    configuration
     * @return  oCookie
     */
    public static function instance($name, array $config = NULL)
    {
        if ( ! isset(oCookie::$instances[$name]))
        {
            // Create a new cookie instance
            oCookie::$instances[$name] = new oCookie($name, $config);
        }

        return oCookie::$instances[$name];
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
     * @var  boolean  Automatically serialize and unserialize cookie value
     */
    protected $_serialize = FALSE;

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
     * @var  boolean  Is cookie value corrupted
     */
    protected $_corrupted = FALSE;

    /**
     * @var  int      Read error code
     */
    protected $_error;

    /**
     * @var  string   Read error message
     */
    protected $_error_msg;

    /**
     * Initiates the cookie.
     *
     * [!!] Cookies can only be created using the [oCookie::instance] method.
     *
     * @param   string  name
     * @param   array   configuration
     * @return  oCookie
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

        if (isset($config['serialize']))
        {
            $this->_serialize = (bool) $config['serialize'];
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
     * oCookie object is rendered to a serialized string. If encryption is
     * enabled, the cookie value will be encrypted.
     *
     *     echo $cookie;
     *
     * @return  string
     * @uses    Encrypt::encode
     */
    public function __toString()
    {
        $value = $this->_value;

        // Serialize the value
        if ($this->_serialize OR $this->_encrypted)
        {
            $value = serialize($value);
        }

        if ($this->_encrypted)
        {
            // Encrypt the data using the default key
            $value = Encrypt::instance($this->_encrypted)->encode($value);
        }

        return (string) $value;
    }

    /**
     * Returns `$this->_corrupted` value which is set to `TRUE` when there were
     * errors when reading the cookie.
     *
     * @return boolean
     */
    public function corrupted()
    {
        return $this->_corrupted;
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
     * @return mixed
     */
    public function error($all = FALSE)
    {
        if ($all)
        {
            $error = array(
                'code'      => $this->_error,
                'message'   => $this->_error_msg,
            );
        }
        else
        {
            $error = $this->_error_msg;
        }
        return $error;
    }

    /**
     * Returns `$this->_loaded` value which is set to `TRUE` when successfully
     * loaded a cookie.
     *
     * @return boolean
     */
    public function loaded()
    {
        return $this->_loaded;
    }

	/**
	 * Sets a signed cookie. Note that all cookie values must be strings.
     * Automatic serialization is possible if enabled in cookie's config.
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
        $value = $this->__toString();

		if ($lifetime === NULL)
		{
			// Use the default lifetime
			$lifetime = $this->_lifetime;
		}

		if ($lifetime !== 0)
		{
			// The lifetime is expected to be a UNIX timestamp
			// To do this, we take the user-configured lifetime in seconds,
			// and add the current time to that. The result is a UNIX timestamp
			// that is precisely $lifetime seconds in the future.
			$lifetime += time();
		}

		// Add the salt to the cookie value
		$value = Cookie::salt($this->_name, $value).'~'.$value;

		return setcookie($this->_name, $value, $lifetime, $this->_path, $this->_domain, $this->_secure, $this->_httponly);
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
	public function value($default = NULL)
	{
		return isset($this->_value) ? $this->_value : $default;
	}

    /**
     * Reads a signed cookie's value. Cookies without signatures will not be
     * read. If the cookie signature is present, but invalid, the cookie will be
     * deleted.
     *
     * @return  void
     * @uses    Cookie::salt
     * @uses    Encrypt::decode
     */
    protected function _read()
    {
		if ( ! isset($_COOKIE[$this->_name]))
		{
			// The cookie does not exist
			$this->_value = NULL;
            return;
		}

        try
        {
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
                    if ($this->_encrypted)
                    {
                        // Decrypt the value
                        $value = Encrypt::instance($this->_encrypted)->decode($value);
                    }

                    if ($this->_encrypted OR $this->_serialize)
                    {
                        // Unserialize the value
                        $value = unserialize($value);
                    }

                    $this->_value = $value;
                    $this->_loaded = TRUE;
                }
                else
                {
                    // The cookie signature is invalid, delete it
                    $this->delete();
                }
            }
        }
        catch (Exception $e)
        {
            $this->_loaded = FALSE;
            $this->_corrupted = TRUE;
            $this->_error = $e->getCode();
            $this->_error_msg = $e->getMessage();
    		$this->_value = NULL;
        }
    }
}
