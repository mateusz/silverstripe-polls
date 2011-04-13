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

### CMS usage

1. Log in the CMS 
1. Go to the _Poll_ section
1. Create a poll, press _Add_, then add a few poll options
1. The further steps depend on how the PollForm has been implemented

### Embed and configure the PollForm

The PollForm knows how to render itself, and is able to render both the selection form and the chart, depending on the value detected in the cookie. 
To embed the PollForm you can create it through your SiteTree object.

	class Page extends SiteTree {
		static $has_one = array(
			'Poll' => 'Poll'
		);

		...

		function PollForm() {
			$pollForm = new PollForm($this, 'PollForm', $this->Poll());	
			// Customise some options
			$pollForm->setChartOption('height', 300);
			$pollForm->setChartOption('width', 300);
			$pollForm->setChartOption('colours', array('FF0000', '00FF00'));
			return $pollForm;
		}

		...
	}

To customise the chart even more, you can subclass the PollForm. 
If you want to make a site-wide changes, you can use a decorator as well.

	class PollFormDecorator extends DataObjectDecorator {
		function replaceChart() {
			return "<img src='my_poll_image.png?values=10,20,10'/>";
		}
	}

	Object::add_extension('PollForm', 'PollFormDecorator');

Then, if you need to modify the form and chart template, have a look at **PollForm.ss**. Here is the default setup.

	<% if Poll.Visible %>
		<h3>$Poll.Title</h3>
		<% if Image %>
			$Poll.Image.ResizedImage(300,200)
		<% end_if %>
		<% if Description %>
			$Poll.Description
		<% end_if %>

		<% if Poll.isVoted %>
			$Chart
		<% else %>
			$DefaultForm
		<% end_if %>
	<% end_if %>
