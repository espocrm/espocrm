<link href="{{basePath}}client/modules/crm/css/vis.css" rel="stylesheet">

{{#if header}}
<div class="row button-container">
    <div class="col-sm-4 col-xs-12">
        <div class="btn-group">
            <button class="btn btn-default" data-action="today">{{translate 'Today' scope='Calendar'}}</button>
            <button class="btn btn-default btn-icon" title="{{translate 'Refresh'}}" data-action="refresh"><span class="fas fa-sync-alt fa-sm"></span></button>
        </div>{{#if calendarTypeSelectEnabled}}<div class="btn-group calendar-type-button-group">
        <div class="btn-group " role="group">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="calendar-type-label">{{calendarTypeLabel}}</span> <span class="caret"></span></button>
            <ul class="dropdown-menu">
                {{#each calendarTypeDataList}}
                    <li>
                        <a href="javascript:" data-action="toggleCalendarType" data-name="{{type}}">
                            <span class="fas fa-check calendar-type-check-icon pull-right{{#if disabled}} hidden{{/if}}"></span> {{label}}
                        </a>
                    </li>
                {{/each}}
            </ul>
        </div>
        <button class="btn btn-default{{#ifNotEqual calendarType 'shared'}} hidden{{/ifNotEqual}} btn-icon" data-action="showSharedCalendarOptions" title="{{translate 'Manage Users' scope='Calendar'}}"><span class="fas fa-pencil-alt fa-sm"></span></button>
        </div>
        {{/if}}
    </div>


    <div class="date-title col-sm-4 hidden-xs"><h4><span style="cursor: pointer;" data-action="refresh" title="{{translate 'Refresh'}}"></span></h4></div>

    <div class="col-sm-4 col-xs-12">
        <div class="btn-group pull-right">
            {{#each ../modeDataList}}
            <button class="btn btn-default{{#ifEqual name ../../mode}} active{{/ifEqual}}" data-action="mode" data-mode="{{name}}" title="{{translate name scope='Calendar' category='modes'}}"><span class="hidden-sm hidden-xs">{{translate name scope='Calendar' category='modes'}}</span><span class="visible-sm visible-xs">{{labelShort}}</span></button>
            {{/each}}
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="fas fa-ellipsis-h"></span></button>
                <ul class="dropdown-menu pull-right">
                    {{#if isCustomViewAvailable}}
                    {{#each viewDataList}}
                        <li>
                            <a href="javascript:" class="{{#ifEqual mode ../../../mode}} active{{/ifEqual}}" data-action="mode" data-mode="{{mode}}">{{name}}</a>
                        </li>
                    {{/each}}
                    {{#if viewDataList.length}}
                        <li class="divider"></li>
                    {{/if}}
                    {{/if}}
                    {{#each scopeFilterDataList}}
                        <li>
                            <a href="javascript:" data-action="toggleScopeFilter" data-name="{{scope}}">
                                <span class="fas fa-check filter-check-icon pull-right{{#if disabled}} hidden{{/if}}"></span> {{translate scope category='scopeNamesPlural'}}
                            </a>
                        </li>
                    {{/each}}
                    {{#if isCustomViewAvailable}}
                        <li class="divider"></li>
                        <li>
                            <a href="javascript:" data-action="createCustomView">{{translate 'Create Shared View' scope='Calendar'}}</a>
                        </li>
                    {{/if}}
                </ul>
            </div>
        </div>
    </div>
</div>
{{/if}}

<div class="timeline"></div>
