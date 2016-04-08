<link href="{{basePath}}client/modules/crm/css/vis.css" rel="stylesheet">

{{#if header}}
<div class="row button-container">
    <div class="col-sm-4 col-xs-2">
        <button class="btn btn-default hidden-xs" data-action="today">{{translate 'Today' scope='Calendar'}}</button>

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
        </div>
        {{/if}}
    </div>


    <div class="date-title col-sm-4 col-xs-4"><h4><span style="cursor: pointer;" data-action="refresh" title="{{translate 'Refresh'}}"></span></h4></div>

    <div class="col-sm-4 col-xs-5">
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
