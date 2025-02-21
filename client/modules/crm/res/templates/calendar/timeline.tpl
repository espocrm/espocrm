<link href="{{basePath}}client/modules/crm/css/vis.css" rel="stylesheet">

{{#if header}}
<div class="row button-container">
    <div class="col-sm-4 col-xs-12">
        <div class="btn-group">
            <button
                class="btn btn-text btn-icon"
                title="{{translate 'Refresh'}}"
                data-action="refresh"
            ><span class="fas fa-sync-alt"></span></button>
            <button
                class="btn btn-text"
                data-action="today"
            >{{translate 'Today' scope='Calendar'}}</button>
        </div>{{#if calendarTypeSelectEnabled}}<div class="btn-group calendar-type-button-group">
        <div class="btn-group " role="group">
            <button
                type="button"
                class="btn btn-text dropdown-toggle"
                data-toggle="dropdown"
            ><span class="calendar-type-label">{{calendarTypeLabel}}</span> <span class="caret"></span></button>
            <ul class="dropdown-menu">
                {{#each calendarTypeDataList}}
                    <li>
                        <a role="button" tabindex="0" data-action="toggleCalendarType" data-name="{{type}}">
                            <span
                                class="fas fa-check calendar-type-check-icon pull-right{{#if disabled}} hidden{{/if}}"
                            ></span> {{label}}
                        </a>
                    </li>
                {{/each}}
            </ul>
        </div>
        <button
            class="btn btn-text{{#ifNotEqual calendarType 'shared'}} hidden{{/ifNotEqual}} btn-icon"
            data-action="showSharedCalendarOptions"
            title="{{translate 'Shared Mode Options' scope='Calendar'}}"
        ><span class="fas fa-pencil-alt fa-sm"></span></button>
        </div>
        {{/if}}
    </div>

    <div class="date-title col-sm-4 hidden-xs">
    <h4><span style="cursor: pointer;" data-action="refresh" title="{{translate 'Refresh'}}"></span></h4></div>

    <div class="col-sm-4 col-xs-12">
        <div class="btn-group pull-right mode-buttons">
            {{{modeButtons}}}
        </div>
    </div>
</div>
{{/if}}

<div class="timeline"></div>
