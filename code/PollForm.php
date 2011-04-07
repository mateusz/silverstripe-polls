<?php
class PollForm extends Form {
	protected $poll;

	function __construct($controller, $name, $poll) {
		if(!$poll) {
			user_error("The poll doesn't exist.", E_USER_ERROR);
		}

		$this->poll = $poll;
		
		$data = array();
		foreach($poll->Choices() as $choice) {
			$data[$choice->ID] = $choice->Title;
		}
		
		if($poll->MultiChoice) {
			$choiceField = new CheckboxSetField('PollChoices', '', $data);
		}
		else {
			$choiceField = new OptionsetField('PollChoices', '', $data);
		}
		
		$fields =  new FieldSet(
			$choiceField
		);
		
		$actions = new FieldSet(
			new FormAction('submitPoll', 'Submit', null, null, 'button')
		);

		$validator = new PollForm_Validator('PollChoices'); 

		parent::__construct($controller, $name, $fields, $actions, $validator);
	}
	
	function submitPoll($data, $form) {
		$choiceIDs = is_array($data['PollChoices']) ? $data['PollChoices'] : array($data['PollChoices']);
		$choicesIDs = implode(',', $choiceIDs);
		$choices = DataObject::get('PollChoice', sprintf('"ID" IN (%s)', $choicesIDs)); 

		if($choices) {
			foreach($choices as $choice) {
				$choice->addVote();
			}
		}
		
		Director::redirectBack();
	}

	function forTemplate() {
		if (!$this->poll || !$this->poll->getVisible() || !$this->poll->Choices()) return null;

		$customised = $this->poll;
		$customised->DefaultForm = $this->renderWith('Form');
		
		return $this->customise($customised)->renderWith(array(
				$this->class,
				'Form'
			));
	}	
}

/**
 * Customise the validation message. Also enforce at least one selection in multi-choice poll (checkboxes!)
 */
class PollForm_Validator extends RequiredFields {
	function php($data) {
		$this->form->Fields()->dataFieldByName('PollChoices')->setCustomValidationMessage('Please select at least one option.');
		return parent::php($data);
	}

	function javascript() {
		$js = <<<JS
		$('PollForm_Poll_PollChoices').requiredErrorMsg = 'Please select at least one option.';
		if (jQuery('#PollForm_Poll_PollChoices').find('input[checked]').length==0) {
			validationError(jQuery('#PollForm_Poll_PollChoices')[0], 'Please select at least one option.', 'required');
		}
JS;
		return $js . parent::javascript();
	}
}
