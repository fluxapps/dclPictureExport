<?php

require_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
require_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/dclPictureExport/vendor/autoload.php';
require_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/dclPictureExport/classes/class.ildclPictureExportConfig.php';

/**
 * Class ilDclPictureExportUIHookGUI
 *
 * @author  Nicolas Schäfli <ns@studer-raimann.ch>
 *
 */
class ilDclPictureExportUIHookGUI extends \ilUIHookPluginGUI
{

    const TAB_NAME = 'picture_export';

    /**
     * @var ilCtrl $ctrl
     */
    private $ctrl;

    /**
     * @var ilDclPictureExportPlugin $pl
     */
    private $pl;

    /**
     * ilDclPictureExportUIHookGUI constructor.
     */
    public function __construct()
    {
        $this->ctrl = $GLOBALS["DIC"]->ctrl();
        $this->pl = $GLOBALS["DIC"]["ilDclPictureExportPlugin"];
    }

    /**
     * @param       $a_comp
     * @param       $a_part
     * @param array $a_par
     */
    function modifyGUI($a_comp, $a_part, $a_par = array())
    {
        if ($a_part == 'tabs' && ilObject::_lookupType($_GET['ref_id'], true) == 'dcl'
            && (!ildclPictureExportConfig::getConfigValue(ildclPictureExportConfig::F_REF_IDS)
		        || in_array($_GET['ref_id'], explode(',', ildclPictureExportConfig::getConfigValue(ildclPictureExportConfig::F_REF_IDS))))
        ) {
            /** @var ilTabsGUI $tabs */
            $tabs = $a_par['tabs'];
            $this->ctrl->setParameterByClass(ilDclPictureExportGUI::class, 'ref_id', $_GET['ref_id']);
            $link = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', ilDclPictureExportGUI::class));
            $tabs->addTab(self::TAB_NAME, $this->pl->txt('tab_picture_export'), $link);
        }
    }

}