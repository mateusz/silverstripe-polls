<?php
class PollAdmin extends ModelAdmin {
	
	static $url_segment = 'polls';
	
	static $menu_title = 'Polls';
	
	static $managed_models = array(
		'Poll'
	);

	public function subsiteCMSShowInMenu() {
		return Config::inst()->get('Poll', 'subsite_cms_show_in_menu');
	}

	public function getEditForm($id = null, $fields = null) {
		$form = parent::getEditForm($id, $fields);
		if (class_exists('Subsite') && !Config::inst()->get('Poll', 'subsites_share_polls')) {
			$gridField = $form->Fields()->fieldByName($this->sanitiseClassName($this->modelClass));
			$list = $gridField->getList()->filter(array('SubsiteID' => Subsite::currentSubsiteID()));
			$gridField->setList($list);
		}
		return $form;
	}
}
