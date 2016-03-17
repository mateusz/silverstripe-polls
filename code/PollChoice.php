<?php
/**
 * This represents a choice that belongs to a {@link Poll}
 * 
 * @package polls
 */
class PollChoice extends DataObject {
	
	private static $db = array(
		'Title' => 'Varchar(255)',
		'Votes' => 'Int',
		'Order' => 'Int'
	);
	
	private static $has_one = array(
		'Poll' => 'Poll'
	);
	
	static $searchable_fields = array(
		'Title'
	);

	static $summary_fields = array(
		'Title',
		'Votes'
	); 
	
	static $default_sort = '"Order" ASC, "Created" ASC';
	
	public function getCMSFields() {
		$polls = Poll::get()->filter('IsActive', 1); 
		$pollsMap = array();
		if($polls) $pollsMap = $polls->map('ID', 'Title');
		
		$fields = FieldList::create(
			TextField::create('Title', '', '', 80),
			DropdownField::create('PollID', 'Belongs to', $pollsMap)
				->setEmptyString('Select...'),
			ReadonlyField::create('Votes')
		); 
		
		return $fields; 
	}
	
	protected function onBeforeWrite() {
		if (!$this->Order) {
			$this->Order = PollChoice::get()->max('Order') + 1;
		}
		parent::onBeforeWrite();
	}
	
	/** 
	 * Increase vote by one and mark its poll has voted
	 */ 
	public function addVote() {
		$poll = $this->Poll();
		
		if($poll && !$poll->hasVoted()) {
			$this->Votes++;
			$this->write();
		}
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

	/**
	 * Return the relative fractional size in comparison with the maximum (winning) result.
	 * Useful for plotting.
	 *
	 * @param $formatPercent If true, return 55%, if not, return 0.55
	 */
	public function getPercentageOfMax($formatPercent = true) {
		$max = $this->Poll()->getMaxVotes();
		if ($max==0) $max = 1;
		$ratio = $this->Votes/$max;
		return $formatPercent ? ((int)($ratio*100)).'%' : $ratio;
	}

	/**
	 * Return the absolute fractional amount for displaying the results.
	 *
	 * @param $formatPercent If true, return 55%, if not, return 0.55
	 */
	public function getPercentageOfTotal($formatPercent = true) {
		$total = $this->Poll()->getTotalVotes();
		if ($total==0) $total = 1;
		$ratio = $this->Votes/$total;
		return $formatPercent ? (number_format($ratio*100, 1)).'%' : $ratio;
	}
}
