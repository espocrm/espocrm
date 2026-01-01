{{#if isEmpty}}
<span class="none-value">{{translate 'None'}}</span>
{{/if}}

<div class="button-container clearfix">
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

<div class="grid-stack grid-stack-12"></div>
