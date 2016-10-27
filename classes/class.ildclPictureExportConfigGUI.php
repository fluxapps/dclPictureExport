<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once "Services/Component/classes/class.ilPluginConfigGUI.php";
require_once "Services/Form/classes/class.ilPropertyFormGUI.php";
require_once "class.ildclPictureExportConfig.php";
/**
 * Class ildclPictureExportConfigGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ildclPictureExportConfigGUI extends ilPluginConfigGUI {

	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form;
	/**
	 * @var ilDclPictureExportPlugin
	 */
	protected $pl;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilLanguage
	 */
	protected $lng;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;

	/**
	 * ildclPictureExportConfigGUI constructor.
	 */
	public function __construct() {
		global $DIC;
		$this->ctrl = $DIC['ilCtrl'];
		$this->tpl = $DIC['tpl'];
		$this->lng = $DIC['lng'];
		$this->pl = ilDclPictureExportPlugin::getInstance();
	}



	/**
	 * @param $cmd
	 */
	public function performCommand($cmd)
	{
		switch ($cmd) {
			case 'configure':
			case 'save':
				$this->$cmd();
				break;
		}
	}

	public function configure() {
		$this->initForm();
		$this->fillForm();
		$this->tpl->setContent($this->form->getHTML());
	}

	public function fillForm()
	{
		$array = array();
		foreach ($this->form->getItems() as $item) {
			$this->getValueForItem($item, $array);
		}
		$this->form->setValuesByArray($array);
	}

	/**
	 * @param ilFormPropertyGUI $item
	 * @param                   $array
	 *
	 * @internal param $key
	 */
	private function getValueForItem($item, &$array)
	{
		$key = $item->getPostVar();
		$array[$key] = ildclPictureExportConfig::getConfigValue($key);
	}

	protected function initForm(){
		$this->form = new ilPropertyFormGUI();
		$this->form->setFormAction($this->ctrl->getFormAction($this));

		$dcl_ref_ids = new ilTextInputGUI($this->pl->txt('input_ref_ids'), ildclPictureExportConfig::F_REF_IDS);
		$dcl_ref_ids->setInfo($this->pl->txt('input_ref_ids_info'));
		$this->form->addItem($dcl_ref_ids);

		$this->form->addCommandButton('save', $this->lng->txt('save'));
	}

	public function save() {
		$this->initForm();
		if (!$this->form->checkInput()) {
			$this->ctrl->redirect($this, 'configure');
		}

		foreach ($this->form->getItems() as $item) {
			$this->saveValueForItem($item);
		}
		ilUtil::sendSuccess($this->pl->txt('updated'), true);
		$this->ctrl->redirect($this, 'configure');
	}

	/**
	 * @param  ilFormPropertyGUI $item
	 */
	protected function saveValueForItem($item) {
		$key = $item->getPostVar();
		ildclPictureExportConfig::set($key, $this->form->getInput($key));
	}
}