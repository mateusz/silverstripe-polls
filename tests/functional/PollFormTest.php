<?php
class PollFormTest extends FunctionalTest {
	
	static $use_draft_site = true;
	
	function setUp() {
		ManifestBuilder::load_test_manifest(); 
		parent::setUp();
		
		// Create sample poll and choices
		$poll = new Poll();
		$poll->Title = "Poll example";
		$poll->IsActive = 1;
		$poll->write();
		
		$choice1 = new PollChoice();
		$choice1->Title = "Choice one";
		$choice1->PollID = $poll->ID;
		$choice1->write(); 
		
		$choice2 = new PollChoice();
		$choice2->Title = "Choice two";
		$choice2->PollID = $poll->ID;
		$choice2->write();
	}
	
	function testFormSubmission() {
		$poll = DataObject::get_one('Poll');
		$choice1 = DataObject::get_one('PollChoice', '"Title" = \'Choice one\'');
		
		$response = $this->get('TestPollForm_Controller');
		$response = $this->submitForm(
			'PollForm_Form',
			null,
			array('PollChoices' => $choice1->ID)
		);
		
		$choice1 = DataObject::get_one('PollChoice', '"Title" = \'Choice one\'');
		$choice2 = DataObject::get_one('PollChoice', '"Title" = \'Choice two\'');
		
		$this->assertEquals(1, $choice1->Votes);
		$this->assertEquals(0, $choice2->Votes);
	}
	
	function testDisplayChart() {
		$poll = DataObject::get_one('Poll');
		$response = $this->get('TestPollForm_Controller', '', '', array('SSPoll_' . $poll->ID => true));
		
		$this->assertContains(
			sprintf('<img src="%s"', $poll->chartURL(600,300)), 
			$response->getBody()
		);
	}
}

class TestPollForm_Controller extends ContentController implements TestOnly {
	
	protected $template = 'TestPollForm';
	
	static $url_handlers = array(
		'$Action//$ID/$OtherID' => "handleAction",
	);
	
	function Link($action = null) {
		return Controller::join_links('TestPollForm_Controller', $this->request->latestParam('Action'), $this->request->latestParam('ID'), $action);
	}
	
	function Form() {
		$poll = DataObject::get_one('Poll');
		
		return new PollForm($this, "Form", $poll->ID);
	}

}