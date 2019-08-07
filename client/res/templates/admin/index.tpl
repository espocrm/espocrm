<div class="page-header"><h3>{{translate 'Administration' scope='Admin'}}</h3></div>

<div class="admin-content">
	<div class="row">
		<div class="col-md-7">
			<div class="admin-tables-container">
				{{#each panelDataList}}
				<div>
					<h4>{{translate label scope='Admin'}}</h4>
					<table class="table table-bordered table-admin-panel" data-name="{{name}}">
					    {{#each itemList}}
					    <tr>
					        <td>
					        	<div>
					        	{{#if iconClass}}
					        	<span class="icon {{iconClass}}"></span>
					        	{{/if}}
					            <a href="{{#if url}}{{url}}{{else}}javascript:{{/if}}"{{#if action}} data-action="{{action}}"{{/if}}>{{translate label scope='Admin' category='labels'}}</a>
					        	</div>
					        </td>
					        <td>{{translate description scope='Admin' category='descriptions'}}</td>
					    </tr>
					    {{/each}}
					</table>
				</div>
				{{/each}}
			</div>
		</div>
		<div class="col-md-5 admin-right-column">
            <div class="notifications-panel-container">{{{notificationsPanel}}}</div>
			<iframe src="{{iframeUrl}}" style="width: 100%; height: {{iframeHeight}}px" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
		</div>
	</div>
</div>