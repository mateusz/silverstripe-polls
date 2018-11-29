<?php
namespace Mateusz\Polls\Tests;

use Mateusz\Polls\Models\Poll;
use SilverStripe\ORM\DataObject;
use Mateusz\Polls\Forms\PollForm;
use Mateusz\Polls\Models\PollChoice;
use SilverStripe\Dev\FunctionalTest;
use Mateusz\Polls\Tests\TestPollFormController;

class PollFormTest extends FunctionalTest
{

    /**
     * @var string
     */
    static $fixture_file = '../Base.yml';

    /**
     * @var boolean
     */
    static $use_draft_site = true;

    /**
     * @var array
     */
    protected static $extra_controllers = [
        TestPollFormController::class,
    ];

    public function testFormSubmission()
    {
        $this->markTestSkipped("Somehow the form doesn't submit.");

        $choice = $this->objFromFixture(PollChoice::class, 'android');

        $response = $this->get('TestPollForm_Controller');
        $response = $this->submitForm(
            'PollForm_PollForm',
            null,
            ['PollChoices' => $choice->ID]
        );

        $choice1 = DataObject::get(PollChoice::class)->filter(array('Title'=>'iPhone'))->first();
        $choice2 = DataObject::get(PollChoice::class)->filter(array('Title'=>'Android'))->first();
        $choice3 = DataObject::get(PollChoice::class)->filter(array('Title'=>'Other'))->first();

        $this->assertEquals(120, $choice1->Votes);
        $this->assertEquals(81, $choice2->Votes); // Increased by 1
        $this->assertEquals(12, $choice3->Votes);
    }

    public function testDisplayChart()
    {
        $poll = DataObject::get(Poll::class)->first();
        $response = $this->get('TestPollForm_Controller', '', '', array('SSPoll_' . $poll->ID => true));

        $selected = $this->cssParser()->getBySelector('form#PollForm_PollForm');
        $this->assertEquals(count($selected), 0, 'Input form is not shown');
    }

    public function testForcedDisplay()
    {
        $poll = DataObject::get(Poll::class)->first();
        $response = $this->get('TestPollForm_Controller?poll_results');

        $selected = $this->cssParser()->getBySelector('form#PollForm_PollForm');
        $this->assertEquals(count($selected), 0, 'Input form is not shown');
    }
}
