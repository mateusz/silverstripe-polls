<?php
namespace Mateusz\Polls\Admins;

use Mateusz\Polls\Models\Poll;
use SilverStripe\Admin\ModelAdmin;

class PollAdmin extends ModelAdmin
{

    /**
     * @var string
     */
    private static $url_segment = 'polls';

    /**
     * @var string
     */
    private static $menu_title = 'Polls';

    /**
     * @var array
     */
    private static $managed_models = [
        Poll::class,
    ];
}
