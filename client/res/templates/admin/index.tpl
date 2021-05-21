<div class="page-header"><h3>{{translate 'Administration' scope='Admin'}}</h3></div>

<div class="admin-content">
	<div class="row">
		<div class="col-md-7">
			<div class="admin-search-container">
				<input type="text" maxlength="64" placeholder="{{translate 'Search'}}"
					data-name="quick-search" class="form-control">
			</div>
			<div class="admin-tables-container">
				{{#each panelDataList}}
				<div class="admin-content-section" data-index="{{@index}}">
					<h4>{{label}}</h4>
					<table class="table table-bordered table-admin-panel" data-name="{{name}}">
					    {{#each itemList}}
					    <tr class="admin-content-row" data-index="{{@index}}">
					        <td>
					        	<div>
					        	{{#if iconClass}}
					        	<span class="icon {{iconClass}}"></span>
					        	{{/if}}
					            <a
                                    href="{{#if url}}{{url}}{{else}}javascript:{{/if}}"
                                    {{#if action}} data-action="{{action}}"{{/if}}
                                >{{label}}</a>
					        	</div>
					        </td>
					        <td>{{translate description scope='Admin' category='descriptions'}}</td>
					    </tr>
					    {{/each}}
					</table>
				</div>
				{{/each}}
				<div class="no-data hidden">{{translate 'No Data'}}</div>
			</div>
		</div>
		<div class="col-md-5 admin-right-column">
            <div class="notifications-panel-container">{{{notificationsPanel}}}</div>

            {{#unless iframeDisabled}}
			<iframe
                src="{{iframeUrl}}"
                style="width: 100%; height: {{iframeHeight}}px"
                frameborder="0"
                webkitallowfullscreen mozallowfullscreen allowfullscreen
            ></iframe>
            {{/unless}}
		</div>
	</div>
</div>