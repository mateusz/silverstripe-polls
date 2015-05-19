<?php
/**
 * This represents a poll data object that should have 2 more {@link PollChoice}s
 * 
 * @package polls
 */
class Poll extends DataObject implements PermissionProvider {
	
	private static $subsites_share_polls = false;
	private static $subsite_cms_show_in_menu = true;

	const COOKIE_PREFIX = 'SSPoll_';
	
	static $db = Array(
		'Title' => 'Varchar(50)',
		'Description' => 'HTMLText',
		'IsActive' => 'Boolean(1)',
		'MultiChoice' => 'Boolean',
		'Embargo' => 'SS_Datetime',
		'Expiry' => 'SS_Datetime',
		'SubsiteID' => 'Int',
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

	private static $vote_handler_class = 'CookieVoteHandler';

	public $voteHandler;

	public function __construct($record = null, $isSingleton = false, $model = null) {
		parent::__construct($record, $isSingleton, $model);
		//create the voteHandler
		$this->voteHandler = Injector::inst()->create(self::config()->get('vote_handler_class'), $this);
	}

	function getCMSFields() {

		if($this->ID != 0) {
			$totalCount = $this->getTotalVotes();
		}
		else {
			$totalCount = 0;
		}
		
		$fields = new FieldList(
			$rootTab = new TabSet("Root",
				new Tab("Main",
					new TextField('Title', 'Poll title (maximum 50 characters)', null, 50),
					new OptionsetField('MultiChoice', 'Single answer (radio buttons)/multi-choice answer (tick boxes)', array(0 => 'Single answer', 1 => 'Multi-choice answer')),
					new OptionsetField('IsActive', 'Poll state', array(1 => 'Active', 0 => 'Inactive')),
					$embargo = new DatetimeField('Embargo', 'Embargo'),
					$expiry = new DatetimeField('Expiry', 'Expiry'),
					new HTMLEditorField('Description', 'Description'),
					$image = new UploadField('Image', 'Poll image')
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
			
			$pollChoicesTable = new GridField(
				'Choices',
				'Choices',
				$this->Choices(),
				$config
			);

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

	function onBeforeWrite() {
		// Limit this poll to the subsite it is defined within
		if (class_exists('Subsite') && !$this->subsites_share_polls) {
			$this->SubsiteID = Subsite::currentSubsiteID();
		}
		return parent::onBeforeWrite();
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
