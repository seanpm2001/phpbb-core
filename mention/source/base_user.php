<?php
/**
 *
 * This file is part of the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 * For full copyright and license information, please see
 * the docs/CREDITS.txt file.
 *
 */

namespace phpbb\mention\source;

abstract class base_user implements source_interface
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\user_loader */
	protected $user_loader;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	/**
	 * Constructor
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\config\config $config, \phpbb\user_loader $user_loader, $phpbb_root_path, $phpEx)
	{
		$this->db = $db;
		$this->config = $config;
		$this->user_loader = $user_loader;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $phpEx;

		if (!function_exists('phpbb_get_user_rank'))
		{
			include($this->phpbb_root_path . 'includes/functions_display.' . $this->php_ext);
		}
	}

	/**
	 * Builds a query based on user input
	 *
	 * @param string $keyword  Search string
	 * @param int    $topic_id Current topic ID
	 * @return string Query ready for execution
	 */
	abstract protected function query($keyword, $topic_id);

	/**
	 * {@inheritdoc}
	 */
	public function get($keyword, $topic_id)
	{
		$keyword = utf8_clean_string($keyword);
		$result = $this->db->sql_query_limit($this->query($keyword, $topic_id), $this->config['mention_names_limit']);

		$names = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_rank = $this->user_loader->get_rank($row['user_id'], true);
			$names['u' . $row['user_id']] = [
				'name'		=> $row['username'],
				'type'		=> 'u',
				'id'		=> $row['user_id'],
				'avatar'	=> [
					'type'	=> 'user',
					'src'	=> $this->user_loader->get_avatar($row['user_id'], true),
				],
				'rank'		=> (isset($user_rank['rank_title'])) ? $user_rank['rank_title'] : '',
			];
		}

		$this->db->sql_freeresult($result);

		return $names;
	}
}
