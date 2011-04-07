# Polls module

## Maintainer 

[Saophalkun Ponlu](mailto:phalkunz@silverstripe.com)

[Mateusz Uzdowski](mailto:mateusz@silverstripe.com)

## Requirements 

SilverStripe 2.4.x

## Installation 

1. Include the module folder in your project root folder and rename it to "polls"
1. Rebuild database schema (dev/build?flush=1)

## Features

- Each visitor, determined by browser cookie, can only vote once 
- Uses [Google chart API](http://code.google.com/apis/chart/) 
- Supports single and multiple-choice polls

## Usage

### Create a poll 

1. Log in the CMS 
1. Go to the **Poll** section
1. Create a poll, press **Add**, then add a few poll options

### Create a poll form

The PollForm knows how to render itself. To embed the PollForm you can for example surface it on your SiteTree object.

`class Page extends SiteTree {
	static $has_one = array(
		'Poll' => 'Poll'
	);

	...

	function PollForm() {
		return new PollForm($this, 'PollForm', $this->Poll());	
	}

	...
}`

It's possible to customise the chart through a decorator.

`class PollDecorator extends DataObjectDecorator {
	function replaceChart() {
		return "<img src='my_poll_image.png?values=10,20,10'/>";
	}
}

Object::add_extension('Poll', 'PollDecorator');`

You can also easily modify the template used for PollForm rendering by creating your own **PollForm.ss**.
