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
		'IsActive'
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
					$image = new ImageField('Image', 'Poll image')
				)
			)
		);
		
		// Add the fields that depend on the poll being already saved and having an ID 
		if($this->ID != 0) {
			$fields->addFieldToTab('Root.Data', new ReadonlyField('Total', 'Total votes', $totalCount));

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
			$fields->addFieldToTab('Root.Choices', $pollChoicesTable);

			$chartTab = new Tab("Chart", new LiteralField('Chart', sprintf(
				'<h1>%s</h1><p>%s</p>', 
				$this->Title, 
				$this->getChart(), 
				$this->Title))
			);
			$rootTab->push($chartTab);
		}
		else {
			$fields->addFieldToTab('Root.Choices', new ReadOnlyField('ChoicesPlaceholder', 'Choices', 'You will be able to add options once you have saved the poll for the first time.'));
		}
				
		$this->extend('updateCMSFields', $fields);
		
		return $fields; 
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

	function maxVotes() {
		$query = DB::query('SELECT MAX("Votes") As "Max" FROM "PollChoice" WHERE "PollID" = ' . $this->ID); 
		$res = $query->nextRecord();

		return $res['Max'];
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

	/**
	 * URL to an chart image that is render by Google Chart API 
	 * @link http://code.google.com/apis/chart/docs/making_charts.html
	 *
	 * @return string
	 */ 
	function getChart() {
		$apiURL = 'https://chart.googleapis.com/chart';
		
		$choices = $this->Choices('', '"Order" ASC');
	
		$extended = $this->extend('getChart');
		if ($extended) return $extended;

		// Fall back to default
		$labels = array();
		$data = array();
		$i = 0;
		if ($choices) foreach($choices as $choice) {
			$labels[] = "t{$choice->Title} ({$choice->Votes}),000000,0,$i,11,1.0,:10:";
			$data[] = $choice->Votes;
			$i++;
		}
		$labels = implode('|', $labels); 
		$data = implode(',', $data);
		$max = (int)(($this->maxVotes()+1) * 1.5);
		$height = $i*35;
		$href = "https://chart.googleapis.com/chart?".
				"chs=300x$height".			// Chart size
				"&cht=bhg".				// Chart type
				"&chco=4D89F9|C6D9FD".	// Alternating bar colours
				"&chds=0,$max".			// Chart scale
				"&chd=t1:$data".		// Data
				"&chm=$labels";			// Custom labels

		return "<img src='$href'/>";
	}

	function generatePollForm($controller, $name) {
		return new PollForm($controller, $name, $this);
	}
}
