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

}