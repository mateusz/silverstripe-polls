<?php
/**
 * This represents a poll data object that should have 2 more {@link PollChoice}s
 * 
 * @package polls
 */
class Poll extends DataObject {
	
	const COOKIE_PREFIX = 'SSPoll_';
	
	static $db = Array(
		'Title' => 'Varchar(50)',
		'Description' => 'HTMLText',
		'IsActive' => 'Boolean(1)',
		'MultiChoice' => 'Boolean'
	);
	static $has_one = array(
		'Image' => 'Image'
	);
	
	static $has_many = Array(
		'Choices' => 'PollChoice'
	);
	
	static $searchable_fields = array(
		'Title', 
		'IsActive' => 'PostgresBooleanSearchFilter'
	);
	
	static $summary_fields = array(
		'Title',
		'IsActive'
	); 
	
	static $default_sort = 'Created DESC';
	
	function getCMSFields() {
		
		if($this->ID != 0) {
			$totalCount = $this->totalVotes();
		}
		else {
			$totalCount = 0;
		}
		
		$fields = new FieldSet(
			$rootTab = new TabSet("Root",
				new Tab("Data",
					new TextField('Title', 'Poll title (maximum 50 characters)', null, 50),
					new OptionsetField('MultiChoice', 'Single answer (radio buttons)/multi-choice answer (tick boxes)', array(0 => 'Single answer', 1 => 'Multi-choice answer')),
					new OptionsetField('IsActive', 'Poll state', array(1 => 'Active', 0 => 'Inactive')),
					new HTMLEditorField('Description', 'Description', 12),
					new ReadonlyField('Total', 'Total votes', $totalCount),
					$image = new ImageField('Image', 'Poll image')
				)
			)
		);
		$image->setCanUploadNewFile(false);
		
		if($this->ID != 0) {
			$chartTab = new Tab("Chart", new LiteralField('Chart', sprintf(
				'<h1>%s</h1><p><img src="%s" title="%s" /></p>', 
				$this->Title, 
				$this->chartURL(), 
				$this->Title))
			);
			
			$rootTab->push($chartTab);
		} 
		
		$pollChoicesTable = new ComplexTableField(
			$this,
			'Choices', // relation name
			'PollChoice', // object class
			array(
				'Order' => '#',
				'Title' => 'Answer option',
				'Votes' => 'Votes'
			), // fields to show in table
			PollChoice::getCMSFields_forPopup(), // form that pops up for edit
			'"PollID" = ' . $this->ID // a filter to only display item associated with this poll
			);
		$pollChoicesTable->setAddTitle( 'a poll choice' );
		$pollChoicesTable->setParentClass('Poll');
		$fields->addFieldToTab('Root.Data', $pollChoicesTable);
				
		$this->extend('updateCMSFields', $fields);
		
		return $fields; 
	}
	
	function getFormFields() {
		$data = array(); 

		foreach($this->Choices() as $choice) {
			$data[$choice->ID] = $choice->Title;
		}
		
		if($this->MultiChoice) {
			$choiceField = new CheckboxSetField('PollChoices', 'Please select at least one of the checkboxes', $data);
		}
		else {
			$choiceField = new OptionsetField('PollChoices', 'Please select one option', $data);
		}
		
		$fields =  new FieldSet(
			new HiddenField('PollID', '', $this->ID),
			$choiceField
		);
		
		$this->extend('updateFormFields', $fields);
		
		return $fields; 
	}
	
	/**
	 * URL to an chart image that is render by Google Chart API 
	 * @link http://code.google.com/apis/chart/docs/making_charts.html
	 *
	 * @return string
	 */ 
	function chartURL($width = null, $height = null) {
		$apiURL = 'https://chart.googleapis.com/chart';
		
		// The sort option helps facilitate writing unit test 
		$choices = $this->Choices('', 'Title ASC'); 
		
		if(!$width) $width = 840;
		if(!$height) $height = 300; 
		
		$formattedLabels = array();
		if ($choices) foreach($choices as $choice) $formattedLabels[] = $choice->Title.'('.$choice->Votes.')';
		$labels = implode('|', $formattedLabels); 
		$data = implode(',', $choices->map('ID', 'Votes'));
		return sprintf(
			"%s?cht=%s&chs=%sx%s&chl=%s&chd=t:%s&chf=bg,s,00000000&chco=%s", 
			$apiURL, 
			'p3', 
			$width, 
			$height, 
			$labels, 
			$data,
			'F9D42D'
		); 
	}
	
	/**
	 * Returns the number of total votes, the sum of all votes from {@link PollChoice}s' votes
	 * 
	 * @return int
	 */ 
	function totalVotes() {
		$query = DB::query('SELECT SUM("Votes") As "Total" FROM "PollChoice" WHERE "PollID" = ' . $this->ID); 
		$res = $query->nextRecord();

		return $res['Total'];
	}
	
	/**
	 * Mark the the poll has been voted by the user, which determined by browser cookie
	 */
	function markAsVoted() {
		Cookie::set(self::COOKIE_PREFIX . $this->ID, 1);
	}
	
	/**
	 * Check if the user, determined by browser cookie, has been submitted a vote to the poll.
	 *
	 * @param integer
	 * @return bool 
	 */
	function isVoted() {
		$cookie = Cookie::get(self::COOKIE_PREFIX . $this->ID);
	
		if($cookie) {
			return true; 
		}
		else {
			return false;
		}
	}
}
