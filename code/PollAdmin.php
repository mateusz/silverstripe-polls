<?php
class PollAdmin extends ModelAdmin
{
    
    public static $url_segment = 'polls';
    
    public static $menu_title = 'Polls';
    
    public static $managed_models = array(
        'Poll'
    );
}
