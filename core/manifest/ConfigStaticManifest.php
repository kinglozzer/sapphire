<?php

/**
 * Allows access to config values set on classes using private statics.
 *
 * @package framework
 * @subpackage manifest
 */
class SS_ConfigStaticManifest {

	/**
	 * @param string $class
	 * @param string $name
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	public function get($class, $name, $default = null) {
		// property_exists() will also check whether the class exists
		if (property_exists($class, $name)) {
			// The config system is case-sensitive so we need to check the exact value
			$reflection = new ReflectionClass($class);
			if(strcmp($reflection->name, $class) === 0) {
				// If we can access this directly, it must be public static
				if (isset($class::${$name})) {
					return null;
				}

				// If it's protected or private static, we must bind a closure to the scope of the
				// class in order to access the property
				$thief = Closure::bind(function($name) {
					return (isset(self::${$name})) ? self::${$name} : null;
				}, null, $class);

				return $thief($name);
			}
		}
		return null;
	}
}
