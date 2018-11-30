<?php
namespace Mateusz\Polls\Models;

use SilverStripe\Forms\Tab;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataObject;
use Mateusz\Polls\Forms\PollForm;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use Mateusz\Polls\Models\PollChoice;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Forms\GridField\GridField;
use Mateusz\Polls\Handlers\CookieVoteHandler;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;

/**
 * This represents a poll data object that should have 2 more {@link PollChoice}s
 *
 * @package polls
 */
class Poll extends DataObject implements PermissionProvider
{

    const COOKIE_PREFIX = 'SSPoll_';

    /**
     * @var string
     */
    private static $table_name = "Poll";

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(50)',
        'Description' => 'HTMLText',
        'IsActive' => 'Boolean(1)',
        'MultiChoice' => 'Boolean',
        'Embargo' => 'Datetime',
        'Expiry' => 'Datetime',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Image' => Image::class,
    ];

    private static $owns = [
        'Image',
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'Choices' => PollChoice::class,
    ];

    /**
     * @var array
     */
    private static $searchable_fields = [
        'Title',
        'IsActive',
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Title',
        'IsActive',
        'Embargo',
        'Expiry',
    ];

    /**
     * @var string
     */
    private static $default_sort = 'Created DESC';

    /**
     * @var string
     */
    private static $vote_handler_class = CookieVoteHandler::class;

    public $voteHandler;

    public function __construct($record = null, $isSingleton = false, $model = null)
    {
        parent::__construct($record, $isSingleton, $model);
        //create the voteHandler
        $this->voteHandler = Injector::inst()->create(self::config()->get('vote_handler_class'), $this);
    }

    public function getCMSFields()
    {
        if ($this->ID != 0) {
            $totalCount = $this->getTotalVotes();
        } else {
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

        // Add the fields that depend on the poll being already saved and having an ID
        if ($this->ID != 0) {
            $config = \SilverStripe\Forms\GridField\GridFieldConfig::create();
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
        } else {
            $fields->addFieldToTab('Root.Choices', new ReadOnlyField('ChoicesPlaceholder', 'Choices', 'You will be able to add options once you have saved the poll for the first time.'));
        }

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    public function getTotalVotes()
    {
        return $this->Choices()->sum('Votes');
    }

    public function getMaxVotes()
    {
        return $this->Choices()->max('Votes');
    }

    /**
     * Mark the the poll has been voted by the user, which determined by browser cookie
     */
    public function markAsVoted()
    {
        return $this->voteHandler->markAsVoted();
    }

    /**
     * Check if the user, determined by browser cookie, has been submitted a vote to the poll.
     *
     * @param integer
     * @return bool
     */
    public function hasVoted()
    {
        return $this->voteHandler->hasVoted();
    }

    /**
     * @deprecated
     */
    public function isVoted()
    {
        Deprecation::notice('0.1', "isVoted has been deprecated, please use hasVoted");
        return $this->hasVoted();
    }

    /**
     * Check if poll should be visible, taking into account the IsActive and embargo/expiry
     */
    public function getVisible()
    {
        if (!$this->IsActive) {
            return false;
        }

        if (($this->Embargo && $this->obj('Embargo')->InFuture()) ||
            ($this->Expiry && $this->obj('Expiry')->InPast())) {
            return false;
        }

        return true;
    }

    public function providePermissions()
    {
        return array(
            "MANAGE_POLLS" => "Manage Polls",
        );
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

    public function canVote()
    {
        return $this->voteHandler->canVote();
    }
}
