{{#unless onlyContent}}
<li data-id="{{model.id}}" class="list-group-item">
{{/unless}}

	<div>
		{{#if statusField}}
		<span class="label label-{{statusStyle}}">{{statusText}}</span>
		{{/if}}
		
		<span class="text-muted">{{{createdBy}}} {{translate 'created'}}
			{{#if isUserStream}} {{parentTypeString}} {{{parent}}} {{else}} {{translate 'this'}} {{parentTypeString}}{{/if}}
			{{#if assignedUserId}} {{translate 'assigned to'}} {{#if assignedToYou}}{{translate 'you'}}{{else}}<a href="#User/view/{{assignedUserId}}">{{assignedUserName}}</a>{{/if}}{{/if}}
		</span>
	</div>
	
	<div>
		<span class="text-muted small">{{{createdAt}}}</span>
	</div>
		
{{#unless onlyContent}}
</li>
{{/unless}}
