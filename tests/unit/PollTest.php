<?php
class PollTest extends SapphireTest {

	static $fixture_file = 'polls/tests/Base.yml';

	function testTotalVotes() {
		$mobilePoll = $this->ObjFromFixture('Poll', 'mobile-poll');
		$this->assertEquals(120 + 80 + 12, $mobilePoll->getTotalVotes());

		$colorPoll = $this->ObjFromFixture('Poll', 'color-poll');
		$this->assertEquals(6 + 15 + 30, $colorPoll->getTotalVotes());
	}

	function testChartURL() {
		$mobilePoll = $this->ObjFromFixture('Poll', 'mobile-poll');
		$pollForm = new PollForm(new Controller(), 'PollForm', $mobilePoll);
		$chart = $pollForm->getChart();
		$this->assertContains('iPhone (120)', $chart);
		$this->assertContains('Android (80)', $chart);
		$this->assertContains('Other (12)', $chart);
	}

	function testMaxVotes() {
		$mobilePoll = $this->ObjFromFixture('Poll', 'mobile-poll');
		$this->assertEquals(120, $mobilePoll->getMaxVotes());

		$colorPoll = $this->ObjFromFixture('Poll', 'color-poll');
		$this->assertEquals(30, $colorPoll->getMaxVotes());
	}

	function testVisible() {
		$mobilePoll = $this->ObjFromFixture('Poll', 'mobile-poll');
		$this->assertTrue($mobilePoll->getVisible());

		$mobilePoll->IsActive = false;
		$mobilePoll->write();
		$this->assertFalse($mobilePoll->getVisible());

		$mobilePoll->IsActive = true;
		$mobilePoll->Embargo = "2010-10-10 10:10:10";
		$mobilePoll->Expiry = "2010-10-11 10:10:10";
		$mobilePoll->write();

		SS_Datetime::set_mock_now('2010-10-10 10:00:00');
		$this->assertFalse($mobilePoll->getVisible());

		SS_Datetime::set_mock_now('2010-10-10 11:00:00');
		$this->assertTrue($mobilePoll->getVisible());

		SS_Datetime::set_mock_now('2010-10-12 10:00:00');
		$this->assertFalse($mobilePoll->getVisible());
	}

	function testMarkAsVoted() {
		// This cannot be tested this way as it relies on cookies.
		/*
		$mobilePoll = $this->ObjFromFixture('Poll', 'mobile-poll');
		$mobilePoll->markAsVoted();
		$this->assertTrue($mobilePoll->hasVoted());
		*/
	}
}
