<?php

require_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/dclPictureExport/vendor/autoload.php';
require_once './Modules/DataCollection/classes/class.ilObjDataCollectionGUI.php';
require_once './Modules/DataCollection/classes/class.ilObjDataCollection.php';
require_once './Modules/DataCollection/classes/class.ilObjDataCollectionAccess.php';

use DclPictureExport\business\ExcelExport;
use DclPictureExport\CommandExecutionService;

/**
 * Class ilDclPictureExportGUI
 *
 * @author  Nicolas Schäfli <ns@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy ilDclPictureExportGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilDclPictureExportGUI: ilObjDataCollectionGUI
 */
class ilDclPictureExportGUI implements CommandExecutionService
{

    const COMMAND_EXPORT = 'doExport';
    const COMMAND_RENDER_GUI = 'render';

    /**
     * @var \ilCtrl $ctrl
     */
    private $ctrl;

    /**
     * @var \ilTemplate $tpl
     */
    private $tpl;

    /**
     * @var ilDclPictureExportPlugin $pl
     */
    private $pl;

    /**
     * @var \ilLanguage $lng
     */
    private $lng;

    /**
     * @var ilTabsGUI
     */
    private $tabs;

    /**
     * @var ilLocatorGUI $breadcrumps
     */
    private $breadcrumps;

    /**
     * @var \ilAccessHandler $access
     */
    private $access;

    /**
     * @var \ilErrorHandling $error
     */
    private $error;

    /**
     * @var ilObjDataCollection $dataCollection
     */
    private $dataCollection;

    /**
     * ilDclPictureExportGUI constructor.
     */
    public function __construct()
    {
        $dic = $GLOBALS["DIC"];
        $this->ctrl = $dic->ctrl();
        $this->tpl = $dic->ui()->mainTemplate();
        $this->pl = $dic["ilDclPictureExportPlugin"];
        $this->lng = $dic->language();
        $this->tabs = $dic->tabs();
        $this->breadcrumps = $dic["ilLocator"];
        $this->access = $dic->access();
        $this->error = $dic["ilErr"];
        $this->dataCollection = new ilObjDataCollection($_GET["ref_id"]);
    }

    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd(self::COMMAND_RENDER_GUI);
        $this->ctrl->saveParameter($this, 'ref_id');

        switch ($cmd) {
            default:
                $this->$cmd();
                break;
        }

        $this->tpl->getStandardTemplate();
        $this->tpl->show();
    }

    /**
     * Render picture export gui.
     * Default command.
     */
    private function render()
    {
        $this->checkAccess();
        $this->setBreadcrumps();
        $this->setHeader();
        $this->initTabs();
        $this->setToolbar();
        //TODO: add gui stuff
    }

    /**
     * Handles the export post.
     */
    private function doExport()
    {
        $this->checkAccess();
        $ref_id = $_GET["ref_id"];
        $table = $_POST["table_id"];

        if($this->checkAccessByTableID($table))
        {
            $export = new ExcelExport($ref_id, $table, $this->buildFilter($table));
            $export->export(ExcelExport::EXPORT_EXCEL, null, true);
            exit();
        }

        \ilUtil::sendFailure($this->pl->txt('export_failed'), true);
        $this->redirectToExportPage();
    }


	/**
	 * build filter of first available tableview for given table_id
	 *
	 * @param $table_id
	 *
	 * @return array
	 */
    private function buildFilter($table_id) {
    	$tableview_id = ilDclCache::getTableCache($table_id)->getFirstTableViewId($this->dataCollection->getRefId());
	    $tableview = ilDclTableView::find($tableview_id);
	    $filter = array();
	    foreach ($tableview->getFieldSettings() as $fs) {
		    if ($filter_value = $fs->getFilterValue()) {
			    $filter = $filter + $filter_value;
		    }
	    }
	    return $filter;
    }

    /**
     * Navigate to the picture export page.
     */
    private function redirectToExportPage()
    {
        $this->ctrl->redirect($this, self::COMMAND_RENDER_GUI);
    }

    /**
     * Init the toolbar backward button.
     */
    private function initTabs()
    {
        $this->ctrl->setParameterByClass('ilObjDataCollectionGUI', 'ref_id', $_GET['ref_id']);
        $this->tabs->setBackTarget($this->pl->txt('button_back_target'), $this->ctrl->getLinkTargetByClass(array(
            'ilRepositoryGUI',
            'ilObjDataCollectionGUI',
        )));
    }

    /**
     * Create header with icon, title and description.
     */
    private function setHeader()
    {
        $obj_id = ilObject2::_lookupObjectId($_GET['ref_id']);
        $this->tpl->setTitleIcon(ilObject2::_getIcon($obj_id));
        $this->tpl->setTitle(ilObject2::_lookupTitle($obj_id));
        $this->tpl->setDescription(ilObject2::_lookupDescription($obj_id));
    }

    /**
     * Set the breadcrumbs.
     * repository --> data collection
     */
    private function setBreadcrumps()
    {
        $obj_id = ilObject2::_lookupObjectId($_GET['ref_id']);
        $title = ilObject2::_lookupTitle($obj_id);

        $this->breadcrumps->addItem($this->lng->txt("repository"), $this->ctrl->getLinkTargetByClass(array(
            "ilRepositoryGUI",
            "ilObjDataCollectionGUI"
        ), ""));
        $this->breadcrumps->addItem($title, $this->ctrl->getLinkTargetByClass(array(
            "ilObjDataCollectionGUI",
            "ilDclPictureExportGUI"
        ), ""));
        $this->tpl->setLocator();
    }

    private function setToolbar()
    {
        $tables = $this->getAvailableTables();

        if(count($tables) > 0)
        {
            /**
             * @var ilToolbarGUI $toolbar
             */
            $toolbar = $GLOBALS["DIC"]["ilToolbar"];
            $toolbar->setFormAction($this->ctrl->getFormActionByClass(self::class, self::COMMAND_EXPORT));

            include_once './Services/Form/classes/class.ilSelectInputGUI.php';
            $table_selection = new ilSelectInputGUI('', 'table_id');
            $table_selection->setOptions($tables);

            $toolbar->addText($this->pl->txt("dcl_table"));
            $toolbar->addInputItem($table_selection);
            $button = ilSubmitButton::getInstance();
            $button->setCaption($this->pl->txt('button_export'), false);
            $button->setCommand(self::COMMAND_EXPORT);
            $toolbar->addButtonInstance($button);
        }
        else
        {
            ilUtil::sendInfo($this->pl->txt("no_exportable_tables_found"), false);
        }
    }

    /**
     * Checks if the user has write access and rise an ILIAS error when no permissions were found for the active user.
     */
    private function checkAccess()
    {
        if (!$this->access->checkAccess("read", "", $_GET['ref_id'])) {
            $this->error->raiseError($this->lng->txt("no_permission"), $this->error->WARNING);
        }
    }

    /**
     * Checks if the current user has the permission to access the table.
     *
     * @param   int     $table_id   The table id which should be used to check the permission.
     * @return bool                 Returns true if the access is permitted otherwise false.
     */
    private function checkAccessByTableID($table_id)
    {
        if ($this->access->checkAccess("read", "", $_GET['ref_id'])) {
            $tables = $this->getAvailableTables();
            if(array_key_exists($table_id, $tables))
            {
                return true;
            }
        }

        $this->error->raiseError($this->lng->txt("no_permission"), $this->error->WARNING);
        return false;
    }

    /**
     * Fetch all available tables readable for the current user.
     *
     * @return string[]     Name value table list.
     */
    private function getAvailableTables() {
        if (ilObjDataCollectionAccess::hasWriteAccess($this->dataCollection->ref_id)) {
            $tables = $this->dataCollection->getTables();
        } else {
            $tables = $this->dataCollection->getVisibleTables();
	        if (!$tables) {
	        	$tables = ilDclCache::getTableCache($this->dataCollection->getFirstVisibleTableId());
	        }
        }
        $tables = $this->getExportableTables($tables);

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
    private function getExportableTables($tableList)
    {
        $matches = [];
        $refId = $_GET["ref_id"];

        foreach ($tableList as $table)
        {
            if($table->getExportEnabled() || $table->hasPermissionToFields($refId))
            {
                $matches[] = $table;
            }
        }

        return $matches;
    }

}