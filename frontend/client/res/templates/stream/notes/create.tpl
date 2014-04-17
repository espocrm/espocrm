{{#unless onlyContent}}
<li data-id="{{model.id}}" class="list-group-item">
{{/unless}}

	<div>
		{{#if statusField}}
		<span class="label label-{{statusStyle}}">{{statusText}}</span>
		{{/if}}
		
		<span class="text-muted">{{{createdBy}}} {{translate 'created' category='stream'}}
			{{#if isUserStream}} {{parentTypeString}} {{{parent}}} {{else}} {{translate 'this' category='stream'}} {{parentTypeString}}{{/if}}
			{{#if assignedUserId}} {{translate 'assigned to' category='stream'}} {{#if assignedToYou}}{{translate 'you' category='stream'}}{{else}}<a href="#User/view/{{assignedUserId}}">{{assignedUserName}}</a>{{/if}}{{/if}}
		</span>
	</div>
	
	<div>
		<span class="text-muted small">{{{createdAt}}}</span>
	</div>
		
{{#unless onlyContent}}
</li>
{{/unless}}
