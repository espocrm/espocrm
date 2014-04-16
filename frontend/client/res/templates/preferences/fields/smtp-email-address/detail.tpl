{{#if value}}
	{{value}}
{{else}}
	<span class="text-danger">{{translate 'userHasNoEmailAddress' category='messages' scope='Admin'}}</span>
	{{#if isAdmin}}
		<a href="#User/edit/{{attr model 'id'}}">{{translate 'Edit'}}</a>
	{{/if}}
{{/if}}
