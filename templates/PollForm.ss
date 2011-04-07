<% if Visible %>
	<h3>$Title</h3>
	<% if Image %>
		$Image.ResizedImage(300,200)
	<% end_if %>
	<% if Description %>
		$Description
	<% end_if %>

	<% if isVoted %>
		$Chart
	<% else %>
		$DefaultForm
	<% end_if %>
<% end_if %>
