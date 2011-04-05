<?php
class PollForm extends Form {
		
	protected $poll = null;
	
	function __construct($controller, $name, $pollid, $chartWidth = null, $chartHeight = null) {
		
		if(!isset($chartWidth)) $chartWidth = 600;
		if(!isset($chartHeight)) $chartHeight = 300;
		
		$this->poll = DataObject::get_one('Poll', '"ID" = ' . $pollid . ' AND  "IsActive" = 1'); 
		if(!$this->poll) {
			user_error("Poll with id \"$pollid\" doesn't exist or not active.", E_USER_ERROR);
		}
		
		if($this->poll->Image()->ID > 0) {
			$imageTag = sprintf('<img class="poll-image" src="%s" />', $this->poll->Image()->SetWidth(168)->URL); 
		}
		else {
			$imageTag = '';
		}

		if($this->poll->isVoted()) {
			$output = sprintf(
				'<div class="poll-chart"><p class="poll-title">%s</p><p class="poll-description">%s</p><p><img src="%s" /></p></div>', 
				$this->poll->Title, 
				$this->poll->Description,
				$this->poll->chartURL($chartWidth, $chartHeight)
			);
			
			$fields = new FieldSet(new LiteralField('PollGraph', $output)); 
			$actions = new FieldSet(); 
		}
		else {
			$choices = array();
			foreach($this->poll->Choices() as $choice) {
				$choices[$choice->ID] = $choice->Title; 
			}
		
			$fields = $this->poll->getFormFields();
			
			$output = sprintf(
				'<p class="poll-title">%s</p><p class="poll-description">%s</p>', 
				$this->poll->Title, 
				$this->poll->Description
			);

			$fields->insertBefore(new LiteralField('Meta', $output), 'PollChoices');
			
			if($imageTag) {
				$this->addExtraClass('has-image');
				$fields->insertBefore(new LiteralField(
					'PollImage', 
					$imageTag
				), 'PollChoices');
			}
			
			$actions = new FieldSet(
				new FormAction('submitPoll', 'Submit',null, null, 'button')
			);
		}

		$validator = new PollForm_Validator('PollChoices'); 

		parent::__construct($controller, $name, $fields, $actions, $validator);
	}
	
	function submitPoll($data, $form) {
		$pollid = $this->poll->ID;
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
