# Polls module

## Maintainer 
[Saophalkun Ponlu](mailto:phalkunz@silverstripe.com)

## Requirements 

SilverStripe 2.4 or higher

## Installation 

1. Include the module folder in your project root folder
1. Rebuild database schema (dev/build?flush=1)

## Features

- Each user, determined by browser cookie, can only vote once 
- Use [Google chart API](http://code.google.com/apis/chart/) 
- Single and multiple-choice polls

## Usage

### Create a poll 

1. Log in the CMS 
1. Go to **Poll** section
1. Create a poll and a few poll choices and attach them to the poll

### Create a poll form

The following code is an example of a simple poll form. 

	class Page_Controller extends ContentController {
		
		... 
		
		function ExamplePoll() {
			$pollID = 2; 
			$chartWidth = 500; 
			$chartHeight = 300; 
		
			$form = new PollForm($this, 'ExamplePoll', $pollID, $chartWidth, $chartHeight);
		
			return $form; 
		}
		
		...
		
	}
	
## Todos

- Logged-in users only 
- View results links