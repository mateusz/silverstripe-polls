<?php
namespace Mateusz\Polls\Tests;

use Mateusz\Polls\Models\Poll;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use Mateusz\Polls\Forms\PollForm;
use SilverStripe\Control\Controller;
use SilverStripe\CMS\Controllers\ContentController;

class TestPollFormController extends Controller implements TestOnly
{
    protected $template = 'TestPollForm';

    private static $url_segment = 'TestPollForm_Controller';

    private static $allowed_actions = array(
        'PollForm'
    );

    public function PollForm()
    {
        $poll = DataObject::get_one(Poll::class);
        return new PollForm($this, "PollForm", $poll);
    }
}
