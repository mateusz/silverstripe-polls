<% if $Poll.Visible %>
	<h3>$Poll.Title</h3>
	
	<% if $Poll.Image %>
		$Poll.Image.ResizedImage(300,200)
	<% end_if %>
	
	
	<% if $Poll.Description %>
		$Poll.Description
	<% end_if %>

	<% if $ShouldShowResults %>
		$Chart
	<% else %>
		$DefaultForm
	<% end_if %>
<% end_if %>
