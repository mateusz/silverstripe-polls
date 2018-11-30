<?php
namespace Mateusz\Polls\Forms;

use SilverStripe\Forms\Form;
use Mateusz\Polls\Models\Poll;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormAction;
use Mateusz\Polls\Models\PollChoice;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\Forms\CheckboxSetField;
use Mateusz\Polls\Forms\PollFormValidator;

class PollForm extends Form
{

    /**
     * @var boolean
     */
    private static $show_results_link = false;

    /**
     * After submission, redirect back to the # anchor. Set to null to disable the feature.
     *
     * @var string
     */
    private static $redirect_to_anchor = 'SSPoll';

    /**
     * @var Poll
     */
    protected $poll;

    protected $chartOptions = [
        'width'=> '300',
        'height'=> '200',
        'colours'=> ['4D89F9', 'C6D9FD'],
    ];

    public function __construct($controller, $name, $poll)
    {
        if (!$poll) {
            user_error("The poll doesn't exist.", E_USER_ERROR);
        }

        $this->poll = $poll;

        $data = array();
        foreach ($poll->Choices() as $choice) {
            $data[$choice->ID] = $choice->Title;
        }

        if ($poll->MultiChoice) {
            $choiceField = new CheckboxSetField('PollChoices', '', $data);
        } else {
            $choiceField = new OptionsetField('PollChoices', '', $data);
        }

        $fields =  new FieldList(
            $choiceField
        );

        if (PollForm::$show_results_link) {
            $showResultsURL = Director::get_current_page()->Link() . '?poll_results';
            $showResultsLink = new LiteralField('ShowPollLink', '<a class="show-results" href="' . $showResultsURL . '">Show results</a>');
            $fields->push($showResultsLink);
        }

        $actions = new FieldList(
            new FormAction('submitPoll', 'Submit', null, null, 'button')
        );

        $validator = new PollFormValidator('PollChoices');

        parent::__construct($controller, $name, $fields, $actions, $validator);
    }

    /**
     * @return void
     */
    public function submitPoll($data, $form)
    {
        $choiceIDs = is_array($data['PollChoices']) ? $data['PollChoices'] : array($data['PollChoices']);
        $choicesIDs = implode(',', $choiceIDs);
        $choices = DataObject::get(PollChoice::class, sprintf('"ID" IN (%s)', $choicesIDs));

        if ($choices) {
            foreach ($choices as $choice) {
                $choice->addVote();
            }
            $form->poll->markAsVoted();
        }

        // Redirect back to anchor (partly copied from Director::redirectBack)
        if (self::$redirect_to_anchor) {
            if ($this->request->requestVar('_REDIRECT_BACK_URL')) {
                $url = $this->request->requestVar('_REDIRECT_BACK_URL');
            } elseif ($this->request->getHeader('Referer')) {
                $url = $this->request->getHeader('Referer');
            } else {
                $url = Director::baseURL();
            }
            $url .= '#'.self::$redirect_to_anchor.'-'.$this->poll->ID;

            $this->controller->redirect($url);
        } else {
            $this->controller->redirectBack();
        }
    }

    /**
     * Renders the poll using the PollForm.ss
     * @return DBHTMLText
     */
    public function forTemplate()
    {
        if (!$this->poll || !$this->poll->getVisible() || !$this->poll->Choices()) {
            return null;
        }

        $this->DefaultForm = $this->renderWith([ 'type' => 'Includes', Form::class ]);
        return $this->customise($this)->renderWith([
            static::class,
            Form::class,
        ]);
    }

    /**
     * Set the default chart options
     * @return void
     */
    public function setChartOption($option, $value)
    {
        $this->chartOptions[$option] = $value;
    }

    /**
     * Access the default chart options
     * @return mixed
     */
    public function getChartOption($option)
    {
        if (isset($this->chartOptions[$option])) {
            return $this->chartOptions[$option];
        }
    }

    /**
     * Access the Poll object associated with this form
     *
     * @return Poll
     */
    public function Poll()
    {
        return $this->poll;
    }

    /**
     * Check if user bypassed the voting form and requested to see the results.
     *
     * @return boolean
     */
    public function isForcedDisplay()
    {
        return isset($_REQUEST['poll_results']);
    }

    /**
     * Collate the information from PollForm and Poll to figure out if the results should be shown.
     *
     * @return boolean
     */
    public function getShouldShowResults()
    {
        return !$this->poll->canVote() || $this->isForcedDisplay();
    }

    /**
     * Get current configuration so it can be used in the template
     *
     * @return string
     */
    public function getShowResultsLink()
    {
        return $this->show_results_link;
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
    public function getChart()
    {
        $extended = $this->extend('replaceChart');
        if (isset($extended) && count($extended)) {
            return array_shift($extended);
        }

        if (!$this->poll || !$this->poll->getVisible() || !$this->poll->Choices()) {
            return null;
        }

        $apiURL = 'https://chart.googleapis.com/chart';

        $choices = $this->poll->Choices()->sort('"Order" ASC');

        // Fall back to default
        $labels = array();
        $data = array();
        $count = 0;
        if ($choices) {
            foreach ($choices as $choice) {
                $labels[] = "t{$choice->Title} ({$choice->Votes}),000000,0,$count,11,1.0,:10:";
                $data[] = $choice->Votes;
                $count++;
            }
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
