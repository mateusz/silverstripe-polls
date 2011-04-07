<?php 
class PollTest extends SapphireTest {
	
	static $fixture_file = 'polls/tests/Base.yml';
	
	function testTotalVotes() {
		$mobilePoll = $this->ObjFromFixture('Poll', 'mobile-poll');
		$this->assertEquals(120 + 80 + 12, $mobilePoll->totalVotes());
		
		$mobilePoll = $this->ObjFromFixture('Poll', 'color-poll');
		$this->assertEquals(6 + 15 + 30, $mobilePoll->totalVotes());
	}
	
	function testChartURL() {
		$mobilePoll = $this->ObjFromFixture('Poll', 'mobile-poll');
		$chart = $mobilePoll->getChart();
		$this->assertContains(urlencode('iPhone (120)'), $chart);
		$this->assertContains(urlencode('Android (80)'), $chart);
		$this->assertContains(urlencode('Other (12)'), $chart);
	}
}
