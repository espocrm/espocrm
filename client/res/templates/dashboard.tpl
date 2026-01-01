<div class="page-header dashboard-header">
    <div class="row">
        <div class="col-sm-4">
            {{#if displayTitle}}
            <h3>{{translate 'Dashboard' category='scopeNames'}}</h3>
            {{/if}}
        </div>
        <div class="col-sm-8 clearfix">
            {{#unless layoutReadOnly}}
            <div class="btn-group pull-right dashboard-buttons">
                <button
                    class="btn btn-text btn-icon dropdown-toggle"
                    data-toggle="dropdown"
                ><span class="fas fa-ellipsis-h"></span></button>
                <ul class="dropdown-menu pull-right dropdown-menu-with-icons">
                    <li>
                        <a role="button" tabindex="0" data-action="editTabs">
                            <span class="fas fa-pencil-alt fa-sm"></span>
                            <span class="item-text">{{translate 'Edit Dashboard'}}</span>
                        </a>
                    </li>
                    {{#if hasAdd}}
                    <li>
                        <a role="button" tabindex="0" data-action="addDashlet">
                            <span class="fas fa-plus"></span>
                            <span class="item-text">{{translate 'Add Dashlet'}}</span>
                        </a>
                    </li>
                    {{/if}}
                </ul>
            </div>
            {{/unless}}
            {{#ifNotEqual dashboardLayout.length 1}}
            <div class="btn-group pull-right dashboard-tabs">
                {{#each dashboardLayout}}
                    <button
                        class="btn btn-text{{#ifEqual @index ../currentTab}} active{{/ifEqual}}"
                        data-action="selectTab"
                        data-tab="{{@index}}"
                    >{{name}}</button>
                {{/each}}
            </div>
            {{/ifNotEqual}}
        </div>
    </div>
</div>
<div class="dashlets grid-stack grid-stack-12">{{{dashlets}}}</div>
