<link href="client/css/gridstack.min.css" rel="stylesheet">

<div class="button-container clearfix">
    <button class="btn btn-default" data-action="editTabs" title="{{translate 'Edit Dashboard'}}"><span class="glyphicon glyphicon-pencil"></span></button>
    <button class="btn btn-default" data-action="addDashlet" title="{{translate 'Add Dashlet'}}"><span class="glyphicon glyphicon-plus"></span></button>

    {{#ifNotEqual dashboardLayout.length 1}}
    <div class="btn-group pull-right dashboard-tabs">
        {{#each dashboardLayout}}
            <button class="btn btn-default{{#ifEqual @index ../currentTab}} active{{/ifEqual}}" data-action="selectTab" data-tab="{{@index}}">{{name}}</button>
        {{/each}}
    </div>
    {{/ifNotEqual}}
</div>


<div class="grid-stack grid-stack-4"></div>