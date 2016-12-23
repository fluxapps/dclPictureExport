<?php

require_once("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");

/**
 * Class ilDclPictureExportPlugin
 *
 * @author  Nicolas Schäfli <ns@studer-raimann.ch>
 */
final class ilDclPictureExportPlugin extends \ilUserInterfaceHookPlugin
{
    /**
     * ilDclPictureExportPlugin constructor.
     */
    public function __construct()
    {
        parent::__construct();

        if(!$GLOBALS["DIC"]->offsetExists("ilDclPictureExportPlugin"))
        {
            $GLOBALS["DIC"]["ilDclPictureExportPlugin"] = $this;
        }
    }

    /**
     * Provides a new or already created ilDclPictureExportPlugin instance.
     *
     * @return ilDclPictureExportPlugin
     */
    public static function getInstance()
    {
        if(!$GLOBALS["DIC"]->offsetExists("ilDclPictureExportPlugin"))
        {
            return new self();
        }

        return $GLOBALS["DIC"]["ilDclPictureExportPlugin"];
    }

    /**
     * Returns the plugin name.
     *
     * @return string
     */
    function getPluginName()
    {
        return "dclPictureExport";
    }



	/**
	 * Fetch all available tables readable for the current user.
	 *
	 * @return string[]     Name value table list.
	 */
	public static function getAvailableTables($ref_id) {
		$dcl = new ilObjDataCollection($_GET['ref_id']);
		if (ilObjDataCollectionAccess::hasWriteAccess($ref_id)) {
			$tables = $dcl->getTables();
		} else {
			$tables = $dcl->getVisibleTables();
			if (!$tables) {
				$tables = ilDclCache::getTableCache($dcl->getFirstVisibleTableId());
			}
		}
		$tables = self::getExportableTables($tables);

		$options = array();
		foreach ($tables as $table) {
			$options[$table->getId()] = $table->getTitle();
		}

		return $options;
	}

	/**
	 * Filters the given table array.
	 *
	 * @param ilDclTable[] $tableList   The table array which should be filtered.
	 *
	 * @return ilDclTable[] The exportable tables which were found in the given array.
	 */
	public static function getExportableTables($tableList)
	{
		$matches = [];
		$refId = $_GET["ref_id"];

		foreach ($tableList as $table)
		{
			if($table->getExportEnabled() || $table->hasPermissionToFields($refId))
			{
				foreach ($table->getFields() as $field) {
					if ($field->getExportable()) {
						$matches[] = $table;
						break;
					}
				}
			}
		}

		return $matches;
	}
}