<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once('./Services/ActiveRecord/class.ActiveRecord.php');

/**
 * Class ildclPictureExportConfig
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ildclPictureExportConfig extends ActiveRecord {

	const F_REF_IDS = 'ref_ids';

	/**
	 * @var array
	 */
	protected static $cache = array();
	/**
	 * @var array
	 */
	protected static $cache_loaded = array();
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_is_notnull       true
	 * @db_fieldtype        text
	 * @db_length           128
	 */
	protected $name;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           4000
	 */
	protected $value;


	/**
	 * @param $name
	 *
	 * @return string
	 */
	public static function getConfigValue($name)
	{
		if ( ! isset(self::$cache_loaded[$name])) {
			$obj = self::find($name);
			if ($obj === NULL) {
				self::$cache[$name] = NULL;
			} else {
				self::$cache[$name] = json_decode($obj->getValue(), true);
			}
			self::$cache_loaded[$name] = true;
		}
		return self::$cache[$name];
	}


	/**
	 * @param $name
	 * @param $value
	 *
	 * @return null
	 */
	public static function set($name, $value)
	{
		/**
		 * @var $obj arConfig
		 */
		$obj = self::findOrGetInstance($name);
		$obj->setValue(json_encode($value));
		if (self::where(array('name' => $name))->hasSets()) {
			$obj->update();
		} else {
			$obj->create();
		}
	}


	public static function returnDbTableName() {
		return 'dcl_picture_exp_c';
	}


	/**
	 * @param string $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}


	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}


	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
}