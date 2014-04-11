{{#unless onlyContent}}
<li data-id="{{model.id}}" class="list-group-item">
{{/unless}}
	
	<div>
		<span class="text-muted">{{{createdBy}}} {{translate 'assigned'}}
			{{#if isUserStream}} {{parentTypeString}} {{{parent}}} {{else}} {{translate 'this'}} {{parentTypeString}}{{/if}} 
			{{translate 'to'}} {{#if assignedToYou}}{{translate 'you'}}{{else}}<a href="#User/view/{{assignedUserId}}">{{assignedUserName}}</a>{{/if}}
		</span>		
	</div>	
	
	<div>
		<span class="text-muted small">{{{createdAt}}}</span>
	</div>
	
{{#unless onlyContent}}
</li>
{{/unless}}
