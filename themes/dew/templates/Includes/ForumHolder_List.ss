<tr class="$EvenOdd">
	<td class="forumCategory odd">
		<h3><a class="topicTitle" href="$Link">$Title</a></h3>
		<% if Content || Moderators %>
			<div class="summary">
				<p>$Content.LimitCharacters(80)</p>
				<% if Moderators %><p>Moderators: <% control Moderators %><a href="$Link">$Nickname</a><% if Last %><% else %>, <% end_if %><% end_control %></p><% end_if %>
			</div>
		<% end_if %>
	</td>

	<td class="count even">
		$NumTopics
	</td>
	<td class="count odd">
		$NumPosts
	</td>
	<td class="even lastPost">
		<% if LatestPost %>
			<% control LatestPost %>
				<p><a class="topicTitle" href="$Link"><% control Topic %>$Title.LimitCharacters(20)<% end_control %></a></p>
				<% control Author %>
					<p class="userInfo">by <% if Link %><a href="$Link"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></a><% else %><span>Anon</span><% end_if %></p>
				<% end_control %>
				
				<p class="userInfo">$Created.Ago</p>
			<% end_control %>
		<% end_if %>
	</td>
</tr>