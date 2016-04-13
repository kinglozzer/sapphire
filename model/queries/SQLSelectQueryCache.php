<?php

class SQLSelectQueryCache implements Flushable {

	/**
	 * @const
	 */
	const CACHE_FOR_REQUEST = '%%CACHE_FOR_REQUEST%%';

	/**
	 * @var array
	 */
	protected $requestCache = array();

	/**
	 * @var string $cacheKey
	 * @var string|int $lifetime
	 * @return mixed
	 */
	public function load($cacheKey, $lifetime) {
		if ($lifetime === self::CACHE_FOR_REQUEST) {
			return isset($this->requestCache[$cacheKey]) ? $this->requestCache[$cacheKey] : null;
		}

		$cache = self::get_persistent_cache();
		return $cache->load($cacheKey);
	}

	/**
	 * @param mixed $data
	 * @param string $cacheKey
	 * @param string|int $lifetime
	 */
	public function save($data, $cacheKey, $lifetime) {
		if ($lifetime === self::CACHE_FOR_REQUEST) {
			$this->requestCache[$cacheKey] = $data;
		} else {
			$cache = self::get_persistent_cache();
			$cache->save($data, $cacheKey, array('querycache'), $lifetime);
		}
	}

	/**
	 * @return Zend_Cache
	 */
	protected static function get_persistent_cache() {
		return SS_Cache::factory('SQLSelect', 'Output', array('automatic_serialization' => true));
	}

	/**
	 * Triggered early in the request when someone requests a flush.
	 */
	public static function flush() {
		$cache = self::get_persistent_cache();
		$backend = $cache->getBackend();

		if(
			$backend instanceof Zend_Cache_Backend_ExtendedInterface
			&& ($capabilities = $backend->getCapabilities())
			&& $capabilities['tags']
		) {
			$cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('querycache'));
		} else {
			$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
	}

}
