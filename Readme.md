# Polls module

## Maintainer 

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

### Embed PollForm in your template

The PollForm knows how to render itself, and is able to render both the selection form and the chart, depending on the value detected in the cookie. It's enough then to include a PollForm in your template. How you create the PollFom object and pass it to the template is up to you, but here's a suggestion: you can create it through your SiteTree object:

```php
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
```

### Customise the chart

You can obtain a good deal of control by redefining the **PollForm.ss** template in your **theme** folder. Here is the default setup:

```html
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
```

And here is advanced setup that renders the poll as simple HTML blocks, using some of polls API functions:

```html
<% if Poll.Visible %>
	<div class="poll">
		<% if Poll.Image %>
			<div class="thumbnail">
				<img src="<% control Poll.Image %>$CroppedImage(150,50).URL<% end_control %>" alt="$Title"/>
			</div>
		<% end_if %>
		<% if Poll.Title %>
			<h3>$Poll.Title</h3>
		<% end_if %>
		<% if Poll.Description %>
			<p>$Poll.Description<br/></p>
		<% end_if %>

		<% if shouldShowResults %>
			<div class='poll-results'>
				<% control Poll.Choices %>
					<div class='poll-results-entry poll-results-entry-$EvenOdd'>
						<span><em>$Title $PercentageOfTotal ($Votes):</em></span>
						<div style='width: $PercentageOfMax;'>&nbsp;</div>
					</div>
				<% end_control %>
			</div>
		<% else %>
			$DefaultForm
		<% end_if %>

		<p class='poll-total'>Total votes: $Poll.TotalVotes</p>
	</div>
<% end_if %>
```

If you want to make a site-wide changes, you can use a decorator and define **replaceChart** function. For example the following
will give you a text-only rendering of results:

```php
class PollFormDecorator extends DataObjectDecorator {
	function replaceChart() {
		$choices = $this->owner->Poll()->Choices('', '"Order" ASC');

		$results = array();
		if ($choices) foreach($choices as $choice) {
			$results[] = "{$choice->Title}: {$choice->Votes}";
		}

		return implode($results, '<br/>');
	}
}

Object::add_extension('PollForm', 'PollFormDecorator');
```


Finally, for a full control of the poll form and the results subclass the PollForm - you can then create form-specific templates or work on the basis of redefining the **getChart** method. This way you can also create multiple parallel presentation layers for the polls.
