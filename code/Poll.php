<?php
/**
 * This represents a poll data object that should have 2 more {@link PollChoice}s
 * 
 * @package polls
 */
class Poll extends DataObject implements PermissionProvider {
	
	const COOKIE_PREFIX = 'SSPoll_';
	
	static $db = Array(
		'Title' => 'Varchar(50)',
		'Description' => 'HTMLText',
		'IsActive' => 'Boolean(1)',
		'MultiChoice' => 'Boolean',
		'Embargo' => 'SS_Datetime',
		'Expiry' => 'SS_Datetime'
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
		'IsActive',
		'Embargo',
		'Expiry'
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
				new Tab("Main",
					new TextField('Title', 'Poll title (maximum 50 characters)', null, 50),
					new OptionsetField('MultiChoice', 'Single answer (radio buttons)/multi-choice answer (tick boxes)', array(0 => 'Single answer', 1 => 'Multi-choice answer')),
					new OptionsetField('IsActive', 'Poll state', array(1 => 'Active', 0 => 'Inactive')),
					$embargo = new DatetimeField('Embargo', 'Embargo'),
					$expiry = new DatetimeField('Expiry', 'Expiry'),
					new HTMLEditorField('Description', 'Description', 12),
					$image = new ImageField('Image', 'Poll image')
				)
			)
		);

		$embargo->getDateField()->setConfig('showcalendar', true);
		$embargo->getTimeField()->setConfig('showdropdown', true);
		$embargo->getDateField()->setConfig('dateformat', 'dd/MM/YYYY');
		$embargo->getTimeField()->setConfig('timeformat', 'h:m a');

		$expiry->getDateField()->setConfig('showcalendar', true);
		$expiry->getTimeField()->setConfig('showdropdown', true);
		$expiry->getDateField()->setConfig('dateformat', 'dd/MM/YYYY');
		$expiry->getTimeField()->setConfig('timeformat', 'h:m a');
		
		// Add the fields that depend on the poll being already saved and having an ID 
		if($this->ID != 0) {
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
			$pollChoicesTable->setPermissions(array("add", "edit", "show", "delete", "export"));
			$fields->addFieldToTab('Root.Data', $pollChoicesTable);

			$fields->addFieldToTab('Root.Data', new ReadonlyField('Total', 'Total votes', $totalCount));

			// Display the results using the default poll chart
			$pollForm = new PollForm(new Controller(), 'PollForm', $this);
			$chartTab = new Tab("Result chart", new LiteralField('Chart', sprintf(
				'<h1>%s</h1><p>%s</p>', 
				$this->Title, 
				$pollForm->getChart(), 
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
	 * TODO: rewrite as Aggregate, so it uses in-built cache?
	 * 
	 * @return int
	 */ 
	function totalVotes($useCache = true) {
		static $_cache;
		if (!isset($_cache) || !$useCache) {
			$query = DB::query('SELECT SUM("Votes") As "Total" FROM "PollChoice" WHERE "PollID" = ' . $this->ID); 
			$res = $query->nextRecord();
			$_cache = $res['Total'];
		}

		return $_cache;
	}

	/**
	 * Find out what is the maximum amount of votes received for one of the options.
	 * TODO: rewrite as Aggregate, so it uses in-built cache?
	 */
	function maxVotes($useCache = true) {
		static $_cache;
		if (!isset($_cache) || !$useCache) {
			$query = DB::query('SELECT MAX("Votes") As "Max" FROM "PollChoice" WHERE "PollID" = ' . $this->ID); 
			$res = $query->nextRecord();
			$_cache = $res['Max'];
		}

		return $_cache;
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
	 * Check if poll should be visible, taking into account the IsActive and embargo/expiry
	 */
	function getVisible() {
		if (!$this->IsActive) return false;
		
		if ($this->Embargo && SS_Datetime::now()->Format('U')<$this->obj('Embargo')->Format('U') || 
			$this->Expiry && SS_Datetime::now()->Format('U')>$this->obj('Expiry')->Format('U')) {
			return false;
		}
		
		return true;
	}
	
	function providePermissions(){
        return array(
            "MANAGE_POLLS" => "Manage Polls",
        );
    }
    
	public function canCreate($member = null) {
		return Permission::check('MANAGE_POLLS', 'any', $member);
	}
	
	public function canEdit($member = null) {
		return Permission::check('MANAGE_POLLS', 'any', $member);
	}
	
	public function canDelete($member = null) {
		return Permission::check('MANAGE_POLLS', 'any', $member);
	}
}
