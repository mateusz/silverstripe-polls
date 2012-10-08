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

		$selected = $this->cssParser()->getBySelector('form#PollForm_PollForm');
		$this->assertEquals(count($selected), 0, 'Input form is not shown');
	}

	function testForcedDisplay() {
		$poll = DataObject::get_one('Poll');
		$response = $this->get('TestPollForm_Controller?poll_results');

		$selected = $this->cssParser()->getBySelector('form#PollForm_PollForm');
		$this->assertEquals(count($selected), 0, 'Input form is not shown');
	}
}

class TestPollForm_Controller extends ContentController implements TestOnly {
	protected $template = 'TestPollForm';
	
	function PollForm() {
		$poll = DataObject::get_one('Poll');
		return new PollForm($this, "PollForm", $poll);
	}

}
