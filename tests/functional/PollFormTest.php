<?php
class PollFormTest extends FunctionalTest {
	static $fixture_file = 'polls/tests/Base.yml';	
	static $use_draft_site = true;
	
	function testFormSubmission() {
		$choice = $this->objFromFixture('PollChoice', 'android');

		$response = $this->get('TestPollForm_Controller');
		$response = $this->submitForm(
			'PollForm_PollForm',
			null,
			array('PollChoices' => $choice->ID)
		);
		
		$choice1 = DataObject::get_one('PollChoice', '"Title" = \'iPhone\'');
		$choice2 = DataObject::get_one('PollChoice', '"Title" = \'Android\'');
		$choice3 = DataObject::get_one('PollChoice', '"Title" = \'Other\'');
		
		$this->assertEquals(120, $choice1->Votes);
		$this->assertEquals(81, $choice2->Votes); // Increased by 1
		$this->assertEquals(12, $choice3->Votes);
	}

	function testDisplayChart() {
		$poll = DataObject::get_one('Poll');
		$response = $this->get('TestPollForm_Controller', '', '', array('SSPoll_' . $poll->ID => true));

		// Create a poll from scratch to compare
		$pollForm = new PollForm(new Controller(), 'PollForm', $poll);
		$this->assertContains($pollForm->getChart(), $this->content());
	}

	function testForcedDisplay() {
		$poll = DataObject::get_one('Poll');
		$response = $this->get('TestPollForm_Controller?poll_results');

		// Create a poll from scratch to compare
		$pollForm = new PollForm(new Controller(), 'PollForm', $poll);
		$this->assertContains($pollForm->getChart(), $this->content());
	}
}

class TestPollForm_Controller extends ContentController implements TestOnly {
	protected $template = 'TestPollForm';
	
	function PollForm() {
		$poll = DataObject::get_one('Poll');
		return new PollForm($this, "PollForm", $poll);
	}

}
