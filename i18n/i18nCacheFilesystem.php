<?php

namespace SilverStripe\i18n;

use Zend\Cache\Storage\Adapter\Filesystem;

/**
 * This class is needed because the Filesystem cache adapter doesn't currently
 * work with zend-i18n
 * 
 * @see https://github.com/zendframework/zend-i18n/issues/44
 */
class i18nCacheFilesystem extends Filesystem
{
	/**
	 * {@inheritdoc}
	 */
	public function setItem($key, $value)
	{
		$value = serialize($value);
		return parent::setItem($key, $value);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getItem($key, & $success = null, & $casToken = null)
	{
		$result = parent::getItem($key, $success, $casToken);
		
		if ($success) {
			$result = unserialize($result);
		}

		return $result;
	}
}
