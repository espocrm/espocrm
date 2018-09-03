
<div class="page-header dashboard-header">
    <div class="row">
        <div class="col-sm-5">
            {{#if displayTitle}}
            <h3>{{translate 'Dashboard' category='scopeNames'}}</h3>
            {{/if}}
        </div>
        <div class="col-sm-7 clearfix">
            {{#unless layoutReadOnly}}
            <div class="btn-group pull-right dashboard-buttons">
                <button class="btn btn-default btn-icon" data-action="editTabs" title="{{translate 'Edit Dashboard'}}"><span class="fas fa-pencil-alt fa-sm"></span></button>
                <button class="btn btn-default btn-icon" data-action="addDashlet" title="{{translate 'Add Dashlet'}}"><span class="fas fa-plus"></span></button>
            </div>
            {{/unless}}
            {{#ifNotEqual dashboardLayout.length 1}}
            <div class="btn-group pull-right dashboard-tabs">
                {{#each dashboardLayout}}
                    <button class="btn btn-default{{#ifEqual @index ../currentTab}} active{{/ifEqual}}" data-action="selectTab" data-tab="{{@index}}">{{name}}</button>
                {{/each}}
            </div>
            {{/ifNotEqual}}
        </div>
    </div>
</div>
<div class="dashlets grid-stack grid-stack-4 row">{{{dashlets}}}</div>

