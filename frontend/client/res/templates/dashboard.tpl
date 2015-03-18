<div class="page-header dashboard-header">
    <div class="row">
        <div class="col-sm-5">
            {{#if displayTitle}}
            <h3>{{translate 'Dashboard' category='scopeNames'}}</h3>
            {{/if}}
        </div>
        <div class="col-sm-7">
            <div class="btn-group pull-right dashboard-buttons">
                <button class="btn btn-default" data-action="editTabs" title="{{translate 'Edit Dashboard'}}"><span class="glyphicon glyphicon-pencil"></span></button>
                <button class="btn btn-default" data-action="addDashlet" title="{{translate 'Add Dashlet'}}"><span class="glyphicon glyphicon-plus"></span></button>
            </div>
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
<div id="dashlets" class="row">{{{dashlets}}}</div>

