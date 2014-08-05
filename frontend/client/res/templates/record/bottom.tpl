{{#each panels}}
	<div class="panel panel-default panel-{{name}}">
		<div class="panel-heading">
			<div class="pull-right btn-group">
				{{#if buttons}}
					{{#each buttons}}
					<button type="button" class="btn btn-default btn-sm action" data-action="{{action}}" data-link="{{link}}" title="{{translate title scope=../../scope}}">{{{html}}}</button>
					{{/each}}
				{{/if}}
				{{#if actions}}
					<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
						<span class="caret"></span>
					</button>
					<ul class="dropdown-menu">
						{{#each actions}}
						<li><a {{#if link}}href="{{link}}"{{else}}href="javascript:"{{/if}} class="action" data-panel="{{../../name}}" {{#if action}} data-action={{action}}{{/if}}{{#each data}} data-{{@key}}="{{./this}}"{{/each}}>{{translate label scope=../scope}}</a></li>
						{{/each}}
					</ul>
				{{/if}}
			</div>
			<h4 class="panel-title">{{title}}</h4>
		</div>
		<div class="panel-body panel-body-{{toDom name}}">
			{{{var name ../this}}}
		</div>
	</div>
{{/each}}

