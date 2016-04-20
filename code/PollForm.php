<?php
class PollForm extends Form {
	
	private static $show_results_link = false; 
	
	/**
	 * After submission, redirect back to the # anchor. Set to null to disable the feature.
	 */
	private static $redirect_to_anchor = null;
	
	protected $poll;

	protected $chartOptions = array(
		'width'=>'300', 
		'height'=>'200', 
		'colours'=>array('4D89F9', 'C6D9FD')
	);

	public function __construct($controller, $name, $poll) {
		if(!$poll) {
			user_error("The poll doesn't exist.", E_USER_ERROR);
		}

		$this->poll = $poll;
		
		$data = array();
		foreach($poll->Choices() as $choice) {
			$data[$choice->ID] = $choice->Title;
		}
		
		if($poll->MultiChoice) {
			$choiceField = CheckboxSetField::create('PollChoices', '', $data);
		}
		else {
			$choiceField = OptionsetField::create('PollChoices', '', $data);
		}
		
		$fields = FieldList::create($choiceField);
		
		if($this->config()->show_results_link) {
			$showResultsURL = Director::get_current_page()->Link() . '?poll_results'; 
			$showResultsLink = LiteralField::create('ShowPollLink', '<a class="show-results" href="' . $showResultsURL . '">Show results</a>'); 
			$fields->push($showResultsLink); 
		}
		
		$actions = FieldList::create(
			FormAction::create('submitPoll', 'Submit', null, null, 'button')
		);

		$validator = PollForm_Validator::create('PollChoices'); 

		parent::__construct($controller, $name, $fields, $actions, $validator);
	}
	
	public function submitPoll($data, $form) {
		$choiceIDs = is_array($data['PollChoices']) ? $data['PollChoices'] : array($data['PollChoices']);
		$choices = PollChoice::get()->filter('ID', $choiceIDs); 

		if($choices) {
			foreach($choices as $choice) {
				$choice->addVote();
			}
			$form->poll->markAsVoted();
		}
		
		// Redirect back to anchor (partly copied from Director::redirectBack)
		if ($this->config()->redirect_to_anchor) {
			if($this->request->requestVar('_REDIRECT_BACK_URL')) {
				$url = $this->request->requestVar('_REDIRECT_BACK_URL');
			} else if($this->request->getHeader('Referer')) {
				$url = $this->request->getHeader('Referer');
			} else {
				$url = Director::baseURL();
			}
			$url .= '#'.$this->config()->redirect_to_anchor.'-'.$this->poll->ID;

			$this->controller->redirect($url);
		}
		else {
			$this->controller->redirectBack();
		}
	}

	/**
	 * Renders the poll using the PollForm.ss
	 */
	public function forTemplate() {
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
	public function setChartOption($option, $value) {
		$this->chartOptions[$option] = $value;
	}
	
	/**
	 * Access the default chart options
	 */
	public function getChartOption($option) {
		if (isset($this->chartOptions[$option])) return $this->chartOptions[$option];
	}

	/**
	 * Access the Poll object associated with this form
	 */
	public function Poll() {
		return $this->poll;
	}
	
	/**
	 * Check if user bypassed the voting form and requested to see the results.
	 */
	public function isForcedDisplay() {
		return isset($_REQUEST['poll_results']); 
	}
	
	/**
	 * Collate the information from PollForm and Poll to figure out if the results should be shown.
	 */
	public function getShouldShowResults() {
		return !$this->poll->canVote() || $this->isForcedDisplay(); 
	}

	/**
	 * Get current configuration so it can be used in the template
	 */
	public function getShowResultsLink() {
		return $this->config()->show_results_link;
	}

	/**
	 * URL to an chart image that is render by Google Chart API 
	 * @link http://code.google.com/apis/chart/docs/making_charts.html
	 * This is quite rudimentary, and can be modified by combination of methods:
	 * - defining a PollForm decorator and replaceChart method
	 * - creating a custom PollForm.ss template in your theme folder
	 * - subclassing this form for full control
	 *
	 * @return string
	 */ 
	public function getChart() {
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
		$max = (int)(($this->poll->getMaxVotes()+1) * 1.5);
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

		return "<img class='poll-chart' src='$href'/>";
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
