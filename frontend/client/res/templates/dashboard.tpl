<div class="page-header">
    <div class="row">
        <div class="col-sm-7">
            {{#if displayTitle}}
            <h3>{{translate 'Dashboard' category='scopeNames'}}</h3>
            {{/if}}
        </div>
        <div class="col-sm-5">
            <div class="btn-group pull-right">
                {{#ifNotEqual dashboardLayout.length 1}}
                {{#each dashboardLayout}}
                    <button class="btn btn-default{{#ifEqual @index ../currentTab}} active{{/ifEqual}}" data-action="selectTab" data-tab="{{@index}}">{{name}}</button>
                {{/each}}
                {{/ifNotEqual}}
                <button class="btn btn-default" data-action="editTabs" title="{{translate 'Edit Dashboard'}}"><span class="glyphicon glyphicon-pencil"></span></button>
                <button class="btn btn-default" data-action="addDashlet" title="{{translate 'Add Dashlet'}}"><span class="glyphicon glyphicon-plus"></span></button>
            </div>
        </div>
    </div>
</div>
<div id="dashlets" class="row">{{{dashlets}}}</div>

