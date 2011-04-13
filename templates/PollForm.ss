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
