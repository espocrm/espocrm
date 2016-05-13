<link href="{{basePath}}client/modules/crm/css/vis.css" rel="stylesheet">

{{#if header}}
<div class="row button-container">
    <div class="col-sm-4 col-xs-6">
        <div class="btn-group">
            <button class="btn btn-default" data-action="today">{{translate 'Today' scope='Calendar'}}</button>
            <button class="btn btn-default" title="{{translate 'Refresh'}}" data-action="refresh"><span class="glyphicon glyphicon-refresh"></span></button>
        </div>
        {{#if calendarTypeSelectEnabled}}
        <div class="btn-group calendar-type-button-group" role="group">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="calendar-type-label">{{calendarTypeLabel}}</span> <span class="caret"></span></button>
            <ul class="dropdown-menu">
                {{#each calendarTypeDataList}}
                    <li>
                        <a href="javascript:" data-action="toggleCalendarType" data-name="{{type}}">
                            <span class="glyphicon glyphicon-ok calendar-type-check-icon pull-right{{#if disabled}} hidden{{/if}}"></span> {{label}}
                        </a>
                    </li>
                {{/each}}
            </ul>
        <button class="btn btn-default{{#ifNotEqual calendarType 'shared'}} hidden{{/ifNotEqual}}" data-action="showSharedCalendarOptions">{{translate 'Manage Users' scope='Calendar'}}</button>
        </div>

        {{/if}}
    </div>


    <div class="date-title col-sm-4 hidden-xs"><h4><span style="cursor: pointer;" data-action="refresh" title="{{translate 'Refresh'}}"></span></h4></div>

    <div class="col-sm-4 col-xs-6">
        <div class="btn-group pull-right">
            {{#each ../modeList}}
            <button class="btn btn-default{{#ifEqual this ../../mode}} active{{/ifEqual}}" data-action="mode" data-mode="{{./this}}">{{translate this scope='Calendar' category='modes'}}</button>
            {{/each}}
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                <ul class="dropdown-menu pull-right">
                    {{#each scopeFilterDataList}}
                        <li>
                            <a href="javascript:" data-action="toggleScopeFilter" data-name="{{scope}}">
                                <span class="glyphicon glyphicon-ok filter-check-icon pull-right{{#if disabled}} hidden{{/if}}"></span> {{translate scope category='scopeNamesPlural'}}
                            </a>
                        </li>
                    {{/each}}
                </ul>
            </div>
        </div>
    </div>
</div>
{{/if}}

<div class="timeline"></div>
