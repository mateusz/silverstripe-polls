# Polls module

[![Build Status](https://secure.travis-ci.org/mateusz/silverstripe-polls.png)](http://travis-ci.org/mateusz/silverstripe-polls)

## Maintainer 

[Mateusz Uzdowski](mailto:mateusz@silverstripe.com)

## Requirements 

master: SilverStripe 3.0.x
0.1: SilverStripe 2.4.x

## Installation 

1. Include the module folder in your project root folder and rename it to "polls"
1. Rebuild database schema (dev/build?flush=1)

## Features

- By default, each visitor, determined by browser cookie, can only vote once
- Uses [Google chart API](http://code.google.com/apis/chart/) 
- Supports single and multiple-choice polls
- Supports Subsites

## Usage

### CMS usage

1. Log in the CMS 
1. Go to the _Poll_ section
1. Create a poll, press _Add_, then add a few poll options
1. The further steps depend on how the PollForm has been implemented

### Connect Poll object with PollForm

The PollForm knows how to render itself, and is able to render both the selection form and the chart. It needs to get a
Poll object as its input though, and it's up to you to provide it: it will depend on your project how you will want to
do this.

Here is the most basic example of how to associate one Poll with each Page:

```php
class Page extends SiteTree {
	static $has_one = array(
		'Poll' => 'Poll'
	);

	...

    function getCMSFields() {
        $fields = parent::getCMSFields();

        $polls = Poll::get();
        if ($polls) { 
            $fields->addFieldsToTab('Root.Main', array(
                DropdownField::create('PollID', 'Poll', $polls->map(), $this->PollID)->setEmptyString('--- Select a poll ---'),
            ));
        }
        else {
            $fields->addFieldsToTab('Root.Main', array(
                new LiteralField('Heading', '<h1>No polls available</h1>'),
				new LiteralField('PollID', '<p>There are no polls available. Please use <a href="admin/polls">the polls
					section</a> to add them.</p>')
            ));
        }

        return $fields;
    }

	...
}
```

Now you should be able to visit your page in the CMS and select the poll from the new dropdown.

### Embed PollForm in your template

Here's a suggestion how to create and expose a PollForm to all your templates:

```php
class Page_Controller extends ContentController {
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

You will then be able to embed this form in your template like that:

```php
$PollForm
```

This allows you to specify where you want the poll to show up. The poll will not appear if the related `SiteTree` object
has no poll associated with it (i.e. $this->Poll() is empty).

### Customise the chart

You can obtain a good deal of control by redefining the **PollForm.ss** template in your **theme** folder. Here is the
default setup:

```html
<% if $Poll.Visible %>
	<h3>$Poll.Title</h3>
	<% if $Image %>
		$Poll.Image.ResizedImage(300,200)
	<% end_if %>
	<% if $Description %>
		$Poll.Description
	<% end_if %>

	<% if $Poll.hasVoted %>
		$Chart
	<% else %>
		$DefaultForm
	<% end_if %>
<% end_if %>
```

And here is advanced setup that renders the poll as simple HTML blocks, using some of polls API functions:

```html
<% if $Poll.Visible %>
	<div class="poll">
		<% if $Poll.Image %>
			<div class="thumbnail">
				<img src="<% control Poll.Image %>$CroppedImage(150,50).URL<% end_control %>" alt="$Title"/>
			</div>
		<% end_if %>
		<% if $Poll.Title %>
			<h3>$Poll.Title</h3>
		<% end_if %>
		<% if $Poll.Description %>
			<p>$Poll.Description<br/></p>
		<% end_if %>

		<% if $shouldShowResults %>
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

If you want to make a site-wide changes, you can use a decorator and define **replaceChart** function. For example the
following will give you a text-only rendering of results:

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


Finally, for a full control of the poll form and the results subclass the PollForm - you can then create form-specific
templates or work on the basis of redefining the **getChart** method. This way you can also create multiple parallel
presentation layers for the polls.

#### Custom Vote Handling

Using the Vote_Backend we can define our own vote handler. The votehandler takes care of storing votes and the logic of
whether a user has voted before.

See `DatabaseVoteHandler` for an example.

### Subsite support

By defualt the Polls menu item will show in all subsites in the CMS. If you only want it to show in the main site, add to your `config/config.yml`:
```yml
Poll:
  subsite_cms_show_in_menu: false
```

By default a poll created within a subsite will only be available in that subsite. To allow polls to be shared between subsites add to your `config/config.yml`:
```yml
Poll:
  subsites_share_polls: true
```
