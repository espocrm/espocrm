{{#unless onlyContent}}
<li data-id="{{model.id}}" class="list-group-item">
{{/unless}}
	
	<div>
		<span class="text-muted">{{{createdBy}}} {{translate 'assigned' category='stream'}}
			{{#if isUserStream}} {{parentTypeString}} {{{parent}}} {{else}} {{translate 'this'}} {{parentTypeString}}{{/if}} 
			{{translate 'to' category='stream'}} {{#if assignedToYou}}{{translate 'you' category='stream'}}{{else}}<a href="#User/view/{{assignedUserId}}">{{assignedUserName}}</a>{{/if}}
		</span>		
	</div>	
	
	<div>
		<span class="text-muted small">{{{createdAt}}}</span>
	</div>
	
{{#unless onlyContent}}
</li>
{{/unless}}
