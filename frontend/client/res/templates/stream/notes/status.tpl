{{#unless onlyContent}}
<li data-id="{{model.id}}" class="list-group-item">
{{/unless}}
	
	<div>
		<span class="label label-{{style}}">{{statusText}}</span>
		<span class="text-muted">		
			{{{createdBy}}} {{translate 'updated' category='stream'}} {{translate field category='fields' scope=parentType}}
			{{#if isUserStream}} {{translate 'on' category='stream'}} {{parentTypeString}} {{{parent}}}{{/if}}		
		</span>
	</div>
	
	<div>
		<span class="text-muted small">{{{createdAt}}}</span>
	</div>

{{#unless onlyContent}}
</li>
{{/unless}}
