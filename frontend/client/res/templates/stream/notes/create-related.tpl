{{#unless onlyContent}}
<li data-id="{{model.id}}" class="list-group-item">
{{/unless}}

	<div>
		
		<span class="text-muted">{{{createdBy}}} {{translate action category='relateActions'}} {{relatedTypeString}} <a href="#{{entityType}}/view/{{entityId}}">{{entityName}}</a> {{translate 'linked to' category='stream'}}
			{{#if isUserStream}} {{parentTypeString}} {{{parent}}} {{else}} {{translate 'this' category='stream'}} {{parentTypeString}}{{/if}}
		</span>
	</div>
	
	<div>
		<span class="text-muted small">{{{createdAt}}}</span>
	</div>
		
{{#unless onlyContent}}
</li>
{{/unless}}
