<?php
/**
*
* This file is part of the phpBB Forum Software package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/

namespace phpbb\finder;

/**
* The finder provides a simple way to locate files in the core and a set of extensions
*/
class factory
{
	protected $cache;
	protected $use_cache;
	protected $phpbb_root_path;
	protected $php_ext;

	/**
	* Creates a new finder instance with its dependencies
	*
	* @param \phpbb\cache\service		$cache A cache instance or null
	* @param bool $not_use_cache		Use cache or not
	* @param string $phpbb_root_path	Path to the phpbb root directory
	* @param string $php_ext			php file extension
	*/
	public function __construct(/*\phpbb\cache\service */ $cache, $use_cache, $phpbb_root_path, $php_ext)
	{
		$this->cache = $cache;
		$this->use_cache = $use_cache;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
	}

	/**
	* The cache variable name used to store $this->cached_queries in $this->cache.
	*
	* Allows the use of multiple differently configured finders with the same cache.
	*
	* @param string $cache_name The name of the cache variable, defaults to
	* _ext_finder
	*/
	public function get($cache_name = '_ext_finder')
	{
		return new finder($this->cache, $this->use_cache, $this->phpbb_root_path, $this->php_ext, $cache_name);
	}
}
