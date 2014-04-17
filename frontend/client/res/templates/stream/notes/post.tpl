{{#unless onlyContent}}
<li data-id="{{model.id}}" class="list-group-item">
{{/unless}}
	
	{{#unless isUserStream}}
	<div class="list-row-buttons pull-right right">		
		{{#if acl.edit}}		
		<div class="btn-group">
		<button type="button" class="btn btn-link btn-sm dropdown-toggle" data-toggle="dropdown">
			<span class="caret"></span>
		</button>		
		<ul class="dropdown-menu pull-right">
			<li><a href="javascript:" class="action" data-action="editRelated" data-id="{{model.id}}">{{translate 'Edit'}}</a></li>
			<li><a href="javascript:" class="action" data-action="removeRelated" data-id="{{model.id}}">{{translate 'Remove'}}</a></li>	
		</ul>
		</div>		
		{{/if}}
	</div>
	{{/unless}}
	
	
	<div class="">
		<span class="text-muted">{{{createdBy}}} {{#if message}}{{translate 'posted' category='stream'}}{{else}}{{#if attachments}}{{translate 'attached' category='stream'}}{{/if}}{{/if}}
		{{#if isUserStream}} {{translate 'on' category='stream'}} {{parentTypeString}} {{{parent}}}{{/if}}
		</span>
	</div>
	
	{{#if message}}
	<div class="">
		<span class="cell cell-message">{{{message}}}</span>
	</div>
	{{/if}}
	
	{{#if attachments}}
	<div>		
		<span class="cell cell-attachments">{{{attachments}}}</span>
	</div>
	{{/if}}
	
	<div class="">
		<span class="text-muted small">{{{createdAt}}}</span>
	</div>
	
{{#unless onlyContent}}
</li>
{{/unless}}
