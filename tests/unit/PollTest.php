<?php
namespace Mateusz\Polls\Tests;

use Mateusz\Polls\Models\Poll;
use Mateusz\Polls\Forms\PollForm;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\FieldType\DBDatetime;

class PollTest extends SapphireTest
{

    static $fixture_file = '../Base.yml';

    public function testTotalVotes()
    {
        $mobilePoll = $this->ObjFromFixture(Poll::class, 'mobile-poll');
        $this->assertEquals(120 + 80 + 12, $mobilePoll->getTotalVotes());

        $colorPoll = $this->ObjFromFixture(Poll::class, 'color-poll');
        $this->assertEquals(6 + 15 + 30, $colorPoll->getTotalVotes());
    }

    public function testChartURL()
    {
        $mobilePoll = $this->ObjFromFixture(Poll::class, 'mobile-poll');
        $pollForm = new PollForm(new Controller(), 'PollForm', $mobilePoll);
        $chart = $pollForm->getChart();
        $this->assertContains('iPhone (120)', $chart);
        $this->assertContains('Android (80)', $chart);
        $this->assertContains('Other (12)', $chart);
    }

    public function testMaxVotes()
    {
        $mobilePoll = $this->ObjFromFixture(Poll::class, 'mobile-poll');
        $this->assertEquals(120, $mobilePoll->getMaxVotes());

        $colorPoll = $this->ObjFromFixture(Poll::class, 'color-poll');
        $this->assertEquals(30, $colorPoll->getMaxVotes());
    }

    public function testVisible()
    {
        $mobilePoll = $this->ObjFromFixture(Poll::class, 'mobile-poll');
        $this->assertTrue($mobilePoll->getVisible());

        $mobilePoll->IsActive = false;
        $mobilePoll->write();
        $this->assertFalse($mobilePoll->getVisible());

        $mobilePoll->IsActive = true;
        $mobilePoll->Embargo = "2010-10-10 10:10:10";
        $mobilePoll->Expiry = "2010-10-11 10:10:10";
        $mobilePoll->write();

        DBDatetime::set_mock_now('2010-10-10 10:00:00');
        $this->assertFalse($mobilePoll->getVisible());

        DBDatetime::set_mock_now('2010-10-10 11:00:00');
        $this->assertTrue($mobilePoll->getVisible());

        DBDatetime::set_mock_now('2010-10-12 10:00:00');
        $this->assertFalse($mobilePoll->getVisible());
    }

    public function testMarkAsVoted()
    {
        // This cannot be tested this way as it relies on cookies.
        $mobilePoll = $this->ObjFromFixture(Poll::class, 'mobile-poll');
        $mobilePoll->markAsVoted();
        $this->assertTrue($mobilePoll->hasVoted());
    }
}
