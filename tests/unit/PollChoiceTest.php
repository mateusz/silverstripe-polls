<?php
namespace Mateusz\Polls\Tests;

use SilverStripe\Dev\SapphireTest;
use Mateusz\Polls\Models\PollChoice;

class PollChoiceTest extends SapphireTest {

    static $fixture_file = '../Base.yml';

    public function testGetPercentageOfMax()
    {
        $choice = $this->ObjFromFixture(PollChoice::class, 'android');
        $this->assertEquals('66%', $choice->getPercentageOfMax());

        $choice = $this->ObjFromFixture(PollChoice::class, 'green');
        $this->assertEquals(0.50, number_format((double)$choice->getPercentageOfMax(false),3));
    }

    public function testGetPercentageOfTotal()
    {
        $choice = $this->ObjFromFixture(PollChoice::class, 'android');
        $this->assertEquals('37.7%', $choice->getPercentageOfTotal());

        $choice = $this->ObjFromFixture(PollChoice::class, 'green');
        $this->assertEquals(0.294, number_format((double)$choice->getPercentageOfTotal(false),3));
    }

    public function testAddVote()
    {
        $choice = $this->ObjFromFixture(PollChoice::class, 'android');
        $choice->addVote();
        // This cannot be tested this way as it relies on cookies.
        // $this->assertTrue($choice->Poll()->hasVoted());
        $this->assertEquals($choice->Votes, 81, 'Vote count incremented');
    }
}

