<?php
namespace Mateusz\Polls\Models;

use Mateusz\Polls\Models\Poll;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Security\Permission;

/**
 * This represents a choice that belongs to a {@link Poll}
 *
 * @package polls
 */
class PollChoice extends DataObject
{
    /**
     * @var string
     */
    private static $table_name = "PollChoice";

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'Votes' => 'Int',
        'Order' => 'Int',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Poll' => Poll::class,
    ];

    /**
     * @var array
     */
    private static $searchable_fields = [
        'Title',
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Order',
        'Title',
        'Votes',
    ];

    /**
     * @var string
     */
    private static $default_sort = '"Order" ASC, "Created" ASC';

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $polls = DataObject::get(Poll::class, '"IsActive" = 1');
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
     *
     * @return void
     */
    public function addVote()
    {
        $poll = $this->Poll();

        if ($poll && !$poll->hasVoted()) {
            $this->Votes++;
            $this->write();
        }
    }

    public function canCreate($member = null, $context = array())
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
     * @param boolean $formatPercent If true, return 55%, if not, return 0.55
     * @return string
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
     * @param boolean $formatPercent If true, return 55%, if not, return 0.55
     * @return string
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
