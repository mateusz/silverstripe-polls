<?php
/**
 * This represents a poll data object that should have 2 more {@link PollChoice}s
 * 
 * @package polls
 */
class Poll extends DataObject implements PermissionProvider {

	const COOKIE_PREFIX = 'SSPoll_';
	
	private static $db = Array(
		'Title' => 'Varchar(100)',
		'Description' => 'HTMLText',
		'IsActive' => 'Boolean(1)',
		'MultiChoice' => 'Boolean',
		'Embargo' => 'SS_Datetime',
		'Expiry' => 'SS_Datetime'
	);

	private static $has_one = array(
		'Image' => 'Image'
	);
	
	private static $has_many = array(
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

	private static $vote_handler_class = 'CookieVoteHandler';

	public $voteHandler;

	public function __construct($record = null, $isSingleton = false, $model = null) {
		parent::__construct($record, $isSingleton, $model);
		//create the voteHandler
		$this->voteHandler = Injector::inst()->create($this->config()->get('vote_handler_class'), $this);
	}

	public function getCMSFields() {

		if($this->ID != 0) {
			$totalCount = $this->getTotalVotes();
		}
		else {
			$totalCount = 0;
		}
		
		$fields = FieldList::create(
			$rootTab = TabSet::create("Root",
				Tab::create("Main",
					TextField::create('Title', _t('Poll.TITLE', 'Poll title'), null, 100)
						->setRightTitle(_t('Poll.MAXCHARACTERS', 'Maximum 100 characters')),
					OptionsetField::create('MultiChoice',
						_t('Poll.ANSWERTYPE', 'Answer type'),
						array(
							0 => 'Single',
							1 => 'Multi-choice'
						)
					)->setRightTitle(_t('Poll.ANSWERTYPEDESCRIPTION', '"Single" uses radio buttons, "Multi-choice" uses tick boxes')),
					OptionsetField::create('IsActive', _t('Poll.STATE', 'Poll state'), array(1 => 'Active', 0 => 'Inactive')),
					$embargo = DatetimeField::create('Embargo', _t('Poll.EMBARGO', 'Embargo')),
					$expiry = DatetimeField::create('Expiry', _t('Poll.EXPIRY', 'Expiry')),
					HTMLEditorField::create('Description', _t('Poll.DESCRIPTION', 'Description')),
					$image = UploadField::create('Image', _t('Poll.IMAGE', 'Poll image'))
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

			$config = GridFieldConfig::create();
			$config->addComponent(new GridFieldToolbarHeader());
			$config->addComponent(new GridFieldAddNewButton('toolbar-header-right'));
			$config->addComponent(new GridFieldDataColumns());
			$config->addComponent(new GridFieldEditButton());
			$config->addComponent(new GridFieldDeleteAction());
			$config->addComponent(new GridFieldDetailForm());
			$config->addComponent(new GridFieldSortableHeader());

			if (class_exists('GridFieldOrderableRows')){
				$config->addComponent(new GridFieldOrderableRows('Order'));
			}

			$pollChoicesTable = GridField::create(
				'Choices',
				_t('Poll.CHOICES', 'Choices'),
				$this->Choices(),
				$config
			);

			$fields->addFieldToTab('Root.Data', $pollChoicesTable);

			$fields->addFieldToTab('Root.Data', ReadonlyField::create('Total', _t('Poll.TOTALVOTES', 'Total votes'), $totalCount));
			
			// Display the results using the default poll chart
			$pollForm = PollForm::create(new Controller(), 'PollForm', $this);
			$chartTab = Tab::create("Result chart", LiteralField::create('Chart', sprintf(
				'<h1>%s</h1><p>%s</p>', 
				$this->Title, 
				$pollForm->getChart(), 
				$this->Title))
			);
			$rootTab->push($chartTab);
		}
		else {
			$fields->addFieldToTab('Root.Choices', ReadonlyField::create('ChoicesPlaceholder', 'Choices', 'You will be able to add options once you have saved the poll for the first time.'));
		}
				
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}

	/**
	 * Get the most recently added Poll that can be visible
	 * 
	 * @return Poll|null A Poll if one is visible, null otherwise
	 */
	public static function get_current_poll(){
		$now = SS_Datetime::now();
		$polls = Poll::get()
			->filter('IsActive', "1")
			->where('"Embargo" IS NULL OR "Embargo" < \'' . $now . "'")
			->where('"Expiry" IS NULL OR "Expiry" > \'' . $now . "'");

		return $polls->Count() ? $polls->First() : null;
	}

	function getTotalVotes() {
		return $this->Choices()->sum('Votes');
	}

	function getMaxVotes() {
		return $this->Choices()->max('Votes');
	}
	
	/**
	 * Mark the the poll has been voted by the user, which determined by browser cookie
	 */
	function markAsVoted() {
		return $this->voteHandler->markAsVoted();
	}
	
	/**
	 * Check if the user, determined by browser cookie, has been submitted a vote to the poll.
	 *
	 * @param integer
	 * @return bool 
	 */
	function hasVoted() {
		return $this->voteHandler->hasVoted();
	}

	/**
	 * @deprecated
	 */
	function isVoted() {
		Deprecation::notice('0.1', "isVoted has been deprecated, please use hasVoted");
		return $this->hasVoted();
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

	public function canVote() {
		return $this->voteHandler->canVote();
	}
}
