<?php
class PollAdmin extends ModelAdmin {
	
	static $url_segment = 'polls';
	
	static $menu_title = 'Polls';
	
	static $managed_models = array(
		'Poll'
	);
	
}