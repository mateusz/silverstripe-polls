<?php
class PollAdmin extends ModelAdmin {
	
	private static
		$url_segment = 'polls',		
		$menu_title = 'Polls',
		$managed_models = array(
			'Poll'
		);
}