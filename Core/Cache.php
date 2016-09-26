<?php

namespace SilverStripe\Core;
use Zend\Cache\StorageFactory;
use Zend\Cache\Storage\StorageInterface;

/**
 * The `[api:Cache]` class provides a bunch of static functions wrapping the Zend cache component
 * in something a little more easy to use with the SilverStripe config system.
 *
 * @see https://docs.silverstripe.org/en/3.4/developer_guides/performance/caching/
 */
class Cache {

	/**
	 * @var array $backends
	 */
	protected static $backends = array();

	/**
	 * @var array $backend_picks
	 */
	protected static $backend_picks = array();

	/**
	 * @var array $cache_lifetime
	 */
	protected static $cache_lifetime = array();

	/**
	 * Initialize the 'default' named cache backend.
	 */
	protected static function init(){
		if (!isset(self::$backends['default'])) {
			$cachedir = TEMP_FOLDER . DIRECTORY_SEPARATOR . 'cache';

			if (!is_dir($cachedir)) {
				mkdir($cachedir);
			}

			/** @skipUpgrade */
			self::$backends['default'] = ['Filesystem', ['cache_dir' => $cachedir], []];

			self::$cache_lifetime['default'] = array(
				'lifetime' => 600,
				'priority' => 1
			);
		}
	}

	/**
	 * Add a new named cache backend.
	 *
	 * @see http://framework.zend.com/manual/en/zend.cache.html
	 *
	 * @param string $name The name of this backend as a freeform string
	 * @param string $adapter The Zend cache adapter ('Filesystem' or 'Sqlite' or ...)
	 * @param array $options The Zend cache options
	 * @param array $plugins Plugins, e.g. serializer
	 */
	public static function add_backend($name, $adapter, $options = [], $plugins = []) {
		self::init();
		self::$backends[$name] = array($adapter, $options, $plugins);
	}

	/**
	 * Pick a named cache backend for a particular named cache.
	 *
	 * The priority call with the highest number will be the actual backend
	 * picked. A backend picked for a specific cache name will always be used
	 * instead of 'any' if it exists, no matter the priority.
	 *
	 * @param string $name The name of the backend, as passed as the first argument to add_backend
	 * @param string $for The name of the cache to pick this backend for (or 'any' for any backend)
	 * @param integer $priority The priority of this pick
	 */
	public static function pick_backend($name, $for, $priority = 1) {
		self::init();

		$current = -1;

		if (isset(self::$backend_picks[$for])) {
			$current = self::$backend_picks[$for]['priority'];
		}

		if ($priority >= $current) {
			self::$backend_picks[$for] = array(
				'name' => $name,
				'priority' => $priority
			);
		}
	}

	/**
	 * Return the cache lifetime for a particular named cache.
	 *
	 * @param string $for
	 *
	 * @return string
	 */
	public static function get_cache_lifetime($for) {
		if(isset(self::$cache_lifetime[$for])) {
			return self::$cache_lifetime[$for];
		}

		return null;
	}

	/**
	 * Set the cache lifetime for a particular named cache
	 *
	 * @param string $for The name of the cache to set this lifetime for (or 'any' for all backends)
	 * @param integer $lifetime The lifetime of an item of the cache, in seconds, or -1 to disable caching
	 * @param integer $priority The priority. The highest priority setting is used. Unlike backends, 'any' is not
	 *                          special in terms of priority.
	 */
	public static function set_cache_lifetime($for, $lifetime=600, $priority=1) {
		self::init();

		$current = -1;

		if (isset(self::$cache_lifetime[$for])) {
			$current = self::$cache_lifetime[$for]['priority'];
		}

		if ($priority >= $current) {
			self::$cache_lifetime[$for] = array(
				'lifetime' => $lifetime,
				'priority' => $priority
			);
		}
	}

	/**
	 * Build a cache object.
	 *
	 * @see http://framework.zend.com/manual/en/zend.cache.html
	 *
	 * @param string $for The name of the cache to build
	 * @param array $instanceOptions (optional) Any frontend options to use.
	 * @return Zend\Cache\Storage\StorageInterface The cache object
	 */
	public static function factory($for, array $instanceOptions = []) {
		self::init();

		$backend_name = 'default';
		$backend_priority = -1;
		$cache_lifetime = self::$cache_lifetime['default']['lifetime'];
		$lifetime_priority = -1;

		foreach(array('any', $for) as $name) {
			if(isset(self::$backend_picks[$name])) {
				if(self::$backend_picks[$name]['priority'] > $backend_priority) {
					$backend_name = self::$backend_picks[$name]['name'];
					$backend_priority = self::$backend_picks[$name]['priority'];
				}
			}

			if (isset(self::$cache_lifetime[$name])) {
				if(self::$cache_lifetime[$name]['priority'] > $lifetime_priority) {
					$cache_lifetime = self::$cache_lifetime[$name]['lifetime'];
					$lifetime_priority = self::$cache_lifetime[$name]['priority'];
				}
			}
		}

		list($backendName, $options, $plugins) = self::$backends[$backend_name];
		$options['namespace'] = $for;

		if ($cache_lifetime >= 0) {
			$options['ttl'] = $cache_lifetime;
		} else {
			$options['ttl'] = 0.1;
		}

		$options = array_merge($options, $instanceOptions);
		return StorageFactory::factory([
			'adapter' => [
				'name' => $backendName,
				'options' => $options
			],
			'plugins' => $plugins
		]);
	}
}
