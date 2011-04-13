<?php
class PollForm extends Form {
	protected $poll;

	protected $chartOptions = array(
		'width'=>'300', 
		'height'=>'200', 
		'colours'=>array('4D89F9', 'C6D9FD')
	);

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

	/**
	 * Renders the poll using the PollForm.ss
	 */
	function forTemplate() {
		if (!$this->poll || !$this->poll->getVisible() || !$this->poll->Choices()) return null;

		$this->DefaultForm = $this->renderWith('Form');
		return $this->customise($this)->renderWith(array(
				$this->class,
				'Form'
			));
	}

	/**
	 * Set the default chart options
	 */
	function setChartOption($option, $value) {
		$this->chartOptions['$option'] = $value;
	}
	
	/**
	 * Access the default chart options
	 */
	function getChartOption($option) {
		if (isset($this->chartOptions['$option'])) return $this->chartOptions['$option'];
	}

	/**
	 * Access the Poll object associated with this form
	 */
	function Poll() {
		return $this->poll;
	}

	/**
	 * URL to an chart image that is render by Google Chart API 
	 * @link http://code.google.com/apis/chart/docs/making_charts.html
	 *
	 * @return string
	 */ 
	function getChart() {
		$extended = $this->extend('replaceChart');
		if (isset($extended) && count($extended)) return array_shift($extended);

		if (!$this->poll || !$this->poll->getVisible() || !$this->poll->Choices()) return null;

		$apiURL = 'https://chart.googleapis.com/chart';
		
		$choices = $this->poll->Choices('', '"Order" ASC');

		// Fall back to default
		$labels = array();
		$data = array();
		$count = 0;
		if ($choices) foreach($choices as $choice) {
			$labels[] = "t{$choice->Title} ({$choice->Votes}),000000,0,$count,11,1.0,:10:";
			$data[] = $choice->Votes;
			$count++;
		}
		$labels = implode('|', $labels); 
		$data = implode(',', $data);
		$max = (int)(($this->poll->maxVotes()+1) * 1.5);
		$height = $this->chartOptions['height'];
		$width = $this->chartOptions['width'];
		$colours = implode($this->chartOptions['colours'], '|');

		$href = "https://chart.googleapis.com/chart?".
				"chs={$width}x$height".	// Chart size
				"&chbh=a".				// Bar width and spacing
				"&cht=bhg".				// Chart type
				"&chco=$colours".		// Alternating bar colours
				"&chds=0,$max".			// Chart scale
				"&chd=t1:$data".		// Data
				"&chm=$labels";			// Custom labels

		return "<img src='$href'/>";
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
