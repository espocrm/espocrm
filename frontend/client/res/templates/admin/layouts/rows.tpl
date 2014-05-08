<div class="button-container">
{{#each buttons}}
	{{button name label=label scope='Admin' style=style}}
{{/each}}
</div>

<style>
	#layout ul {
		width: 100%;
		min-height: 100px;
		padding: 0;
		list-style-type: none;
		margin: 0;
	}
	
	#layout ul li {
		list-style: none;
		border: 1px solid #CCC;
		margin: 5px;
		padding: 5px;
		height: 32px;
	}
	
	#layout header {
		font-weight: bold;
	}
	
	#layout ul > li .left {
		float: left;
	}
	
	#layout ul > li {
		background-color: #FFF;
	}
	
	#layout ul.enabled > li .right {
		float: right;
	}
	
	#layout ul.disabled > li .right {
		display: none;
	}
	

	
	#layout ul > li .width {
		font-size: small;
	}
	
	#layout ul.disabled > li .width {
		display: none;
	}
	
	#layout label {
		font-weight: normal;
	}
	
	.enabled li a.edit-field {
		display: none;
	}
	
	.enabled li:hover a.edit-field {
		display: block;
	}
</style>

<div id="layout" class="row">
	<div class="col-sm-5">
		<div class="well">
			<header>{{translate 'Enabled' scope='Admin'}}</header>
			<ul class="enabled connected">
				{{#each layout}}
					<li draggable="true" {{#each ../dataAttributes}}data-{{this}}="{{prop ../this this}}" {{/each}}>
						<div class="left">
							<label>{{label}}</label>
						</div>
						{{#if ../editable}}
						<div class="right"><a href="javascript:" data-action="edit-field" class="edit-field"><i class="glyphicon glyphicon-pencil"></i></a></div>
						{{/if}}
					</li>
				{{/each}}
			</ul>
		</div>
	</div>
	<div class="col-sm-5">
		<div class="well">
			<header>{{translate 'Disabled' scope='Admin'}}</header>
			<ul class="disabled connected">
				{{#each disabledFields}}
					<li draggable="true" data-name="{{name}}">
						<div class="left">
							<label>{{label}}</label>
						</div>
						{{#if ../editable}}
						<div class="right"><a href="javascript:" data-action="edit-field" class="edit-field"><i class="glyphicon glyphicon-pencil"></i></a></div>
						{{/if}}
					</li>
				{{/each}}
			</ul>
		</div>
	</div>
</div>

<div id="edit-dialog-tpl" style="display: none;">
		{{#each	dataAttributes}}
			{{#ifNotEqual this 'name'}}	
				<div class="form-group">
					<label>{{translate this}}</label>
					{{#ifPropEquals ../../dataAttributesDefs this 'text'}}
						<input type="text" name="{{../this}}" value="" size="8" maxlength="8" class="form-control input-small">
					{{/ifPropEquals}}						
					{{#ifPropEquals ../../dataAttributesDefs this 'bool'}}						
						<select name="{{../this}}" class="form-control input-small">
							<option value="">no</option>
							<option value="true">yes</option>
						</select>
					{{/ifPropEquals}}						
				</div>
			{{/ifNotEqual}}
		{{/each}}		
</div>
