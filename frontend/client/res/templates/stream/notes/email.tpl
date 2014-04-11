{{#unless onlyContent}}
<li data-id="{{model.id}}" class="list-group-item">
{{/unless}}	
	
	<div>
		<span class="text-muted"><span class="label label-primary"><span class="glyphicon glyphicon-envelope "></span></span> {{translate 'Email'}} <a href="#Email/view/{{emailId}}">{{emailName}}</a> has been received 
			{{#if isUserStream}} {{translate 'for'}} {{parentTypeString}} {{{parent}}} {{/if}} 
		</span>		
	</div>
	
	
	<div>
		<span class="text-muted small">{{{createdAt}}}</span>
	</div>
	
{{#unless onlyContent}}
</li>
{{/unless}}
