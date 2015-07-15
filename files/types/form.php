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

namespace phpbb\files\types;

use \phpbb\files\factory;
use \phpbb\files\filespec;
use \phpbb\files\upload;
use \phpbb\language\language;
use \phpbb\plupload\plupload;
use \phpbb\request\request_interface;

class form extends base
{
	/** @var factory Files factory */
	protected $factory;

	/** @var language */
	protected $language;

	/** @var plupload */
	protected $plupload;

	/** @var request_interface */
	protected $request;

	/** @var upload */
	protected $upload;

	/**
	 * Construct a form upload type
	 *
	 * @param factory           $factory
	 * @param request_interface $request
	 */
	public function __construct(factory $factory, language $language, plupload $plupload, request_interface $request)
	{
		$this->factory = $factory;
		$this->language = $language;
		$this->plupload = $plupload;
		$this->request = $request;
	}

	/**
	 * {@inheritdoc}
	 */
	public function upload()
	{
		$args = func_get_args();
		return $this->form_upload($args[0]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function set_upload(upload $upload)
	{
		$this->upload = $upload;

		return $this;
	}

	/**
	 * Form upload method
	 * Upload file from users harddisk
	 *
	 * @param string $form_name Form name assigned to the file input field (if it is an array, the key has to be specified)
	 *
	 * @return filespec $file Object "filespec" is returned, all further operations can be done with this object
	 * @access public
	 */
	protected function form_upload($form_name)
	{
		$upload = $this->request->file($form_name);
		unset($upload['local_mode']);

		$result = $this->plupload->handle_upload($form_name);
		if (is_array($result))
		{
			$upload = array_merge($upload, $result);
		}

		/** @var filespec $file */
		$file = $this->factory->get('filespec')
			->set_upload_ary($upload)
			->set_upload_namespace($this->upload);

		if ($file->init_error())
		{
			$file->error[] = '';
			return $file;
		}

		// Error array filled?
		if (isset($upload['error']))
		{
			$error = $this->upload->assign_internal_error($upload['error']);

			if ($error !== false)
			{
				$file->error[] = $error;
				return $file;
			}
		}

		// Check if empty file got uploaded (not catched by is_uploaded_file)
		if (isset($upload['size']) && $upload['size'] == 0)
		{
			$file->error[] = $this->language->lang($this->upload->error_prefix . 'EMPTY_FILEUPLOAD');
			return $file;
		}

		// PHP Upload filesize check
		$file = $this->check_upload_size($file);
		if (sizeof($file->error))
		{
			return $file;
		}

		// Not correctly uploaded
		if (!$file->is_uploaded())
		{
			$file->error[] = $this->language->lang($this->upload->error_prefix . 'NOT_UPLOADED');
			return $file;
		}

		$this->upload->common_checks($file);

		return $file;
	}
}
