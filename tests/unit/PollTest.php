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
		$this->assertEquals(
			"https://chart.googleapis.com/chart?cht=p3&chs=300x200&chl=Android(80)|iPhone(120)|Others(12)&chd=t:80,120,12&chf=bg,s,00000000&chco=F9D42D",
			$mobilePoll->chartURL('300', '200')
		);
	}
}