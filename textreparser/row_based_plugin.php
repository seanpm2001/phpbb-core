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

namespace phpbb\textreparser;

abstract class row_based_plugin extends base
{
	/**
	* @var \phpbb\db\driver\driver_interface
	*/
	protected $db;

	/**
	* Constructor
	*
	* @param \phpbb\db\driver\driver_interface $db Database connection
	*/
	public function __construct(\phpbb\db\driver\driver_interface $db)
	{
		$this->db = $db;
	}

	/**
	* Return the name of the column that correspond to each field
	*
	* @return array
	*/
	abstract public function get_columns();

	/**
	* Return the name of the table used by this plugin
	*
	* @return string
	*/
	abstract public function get_table_name();

	/**
	* Add fields to given row, if applicable
	*
	* The enable_* fields are not always saved to the database. Sometimes we need to guess their
	* original value based on the text content or possibly other fields
	*
	* @param  array $row Original row
	* @return array      Complete row
	*/
	protected function add_missing_fields(array $row)
	{
		if (!isset($row['enable_bbcode'], $row['enable_smilies'], $row['enable_magic_url']))
		{
			$row += array(
				'enable_bbcode'    => !empty($row['bbcode_uid']),
				'enable_smilies'   => $this->guess_smilies($row),
				'enable_magic_url' => $this->guess_magic_url($row),
			);
		}

		// Those BBCodes are disabled based on context and user permissions and that value is never
		// stored in the database. Here we test whether they were used in the original text.
		$bbcodes = array('flash', 'img', 'quote', 'url');
		foreach ($bbcodes as $bbcode)
		{
			$field_name = 'enable_' . $bbcode;
			$row[$field_name] = $this->guess_bbcode($row, $bbcode);
		}

		return $row;
	}

	/**
	* {@inheritdoc}
	*/
	public function get_max_id()
	{
		$columns = $this->get_columns();

		$sql = 'SELECT MAX(' . $columns['id'] . ' AS max_id FROM ' . $this->get_table_name();
		$result = $this->db->sql_query($sql);
		$max_id = (int) $this->db->sql_fetchfield('max_id');
		$this->db->sql_freeresult($result);

		return $max_id;
	}

	/**
	* {@inheritdoc}
	*/
	protected function get_records($min_id, $max_id)
	{
		$result = $this->db->sql_query($this->get_records_query($min_id, $max_id));
		while ($row = $this->db->sql_fetchrow($result))
		{
			$records[] = $this->add_missing_fields($row);
		}
		$this->db->sql_freeresult($result);

		return $records;
	}

	/**
	* Generate the query that retrieves all records for given range
	*
	* @param  integer $min_id Lower bound
	* @param  integer $max_id Upper bound
	* @return string          SQL query
	*/
	protected function get_records_query($min_id, $max_id)
	{
		$columns = $this->get_columns();
		$fields  = array();
		foreach ($columns as $field_name => $column_name)
		{
			if ($column_name === $field_name)
			{
				$fields[] = $column_name;
			}
			else
			{
				$fields[] = $column_name . ' AS ' . $field_name;
			}
		}

		$sql = 'SELECT ' . implode(', ', $fields) . '
			FROM ' . $this->get_table_name() . '
			WHERE ' . $columns['id'] . ' BETWEEN ' . $min_id . ' AND ' . $max_id;

		return $sql;
	}

	/**
	* {@inheritdoc}
	*/
	protected function save_record(array $record)
	{
		$columns = $this->get_columns();

		$sql = 'UPDATE ' . $this->get_table_name() . '
			SET ' . $columns['text'] . " = '" . $this->db->sql_escape($record['text']) . "'
			WHERE " . $columns['id'] . ' = ' . $record['id'];
		$this->db->sql_query($sql);
	}
}
