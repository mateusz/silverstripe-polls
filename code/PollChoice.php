<?php
/**
 * This represents a choice that belongs to a {@link Poll}
 * 
 * @package polls
 */
class PollChoice extends DataObject
{
    
    public static $db = array(
        'Title' => 'Varchar(255)',
        'Votes' => 'Int',
        'Order' => 'Int'
    );
    
    public static $has_one = array(
        'Poll' => 'Poll'
    );
    
    public static $searchable_fields = array(
        'Title'
    );

    public static $summary_fields = array(
        'Order',
        'Title',
        'Votes'
    );
    
    public static $default_sort = '"Order" ASC, "Created" ASC';
    
    public function getCMSFields()
    {
        $polls = DataObject::get('Poll', '"IsActive" = 1');
        $pollsMap = array();
        if ($polls) {
            $pollsMap = $polls->map('ID', 'Title', '--- Select a poll ---');
        }
        
        $fields = new FieldList(
            new TextField('Title', '', '', 80),
            new DropdownField('PollID', 'Belongs to', $pollsMap),
            new ReadonlyField('Votes')
            //new TextField('Order')
        );
        
        return $fields;
    }
    
    /** 
     * Increase vote by one and mark its poll has voted
     */
    public function addVote()
    {
        $poll = $this->Poll();
        
        if ($poll && !$poll->hasVoted()) {
            $this->Votes++;
            $this->write();
        }
    }
    
    public function canCreate($member = null)
    {
        return Permission::check('MANAGE_POLLS', 'any', $member);
    }
    
    public function canEdit($member = null)
    {
        return Permission::check('MANAGE_POLLS', 'any', $member);
    }
    
    public function canDelete($member = null)
    {
        return Permission::check('MANAGE_POLLS', 'any', $member);
    }

    /**
     * Return the relative fractional size in comparison with the maximum (winning) result.
     * Useful for plotting.
     *
     * @param $formatPercent If true, return 55%, if not, return 0.55
     */
    public function getPercentageOfMax($formatPercent = true)
    {
        $max = $this->Poll()->getMaxVotes();
        if ($max==0) {
            $max = 1;
        }
        $ratio = $this->Votes/$max;
        return $formatPercent ? ((int)($ratio*100)).'%' : $ratio;
    }

    /**
     * Return the absolute fractional amount for displaying the results.
     *
     * @param $formatPercent If true, return 55%, if not, return 0.55
     */
    public function getPercentageOfTotal($formatPercent = true)
    {
        $total = $this->Poll()->getTotalVotes();
        if ($total==0) {
            $total = 1;
        }
        $ratio = $this->Votes/$total;
        return $formatPercent ? (number_format($ratio*100, 1)).'%' : $ratio;
    }
}
