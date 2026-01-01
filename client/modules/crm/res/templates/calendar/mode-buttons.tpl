{{#each visibleModeDataList}}
<button class="btn btn-text strong{{#ifEqual mode ../mode}} active{{/ifEqual}}" data-action="mode" data-mode="{{mode}}" title="{{label}}"><span class="hidden-md hidden-sm hidden-xs">{{label}}</span><span class="visible-md visible-sm visible-xs">{{labelShort}}</span></button>
{{/each}}
<div class="btn-group" role="group">
    <button type="button" class="btn btn-text dropdown-toggle" data-toggle="dropdown"><span class="fas fa-ellipsis-h"></span></button>
    <ul class="dropdown-menu pull-right">
        {{#each hiddenModeDataList}}
            <li>
                <a
                    role="button"
                    tabindex="0"
                    class="{{#ifEqual mode ../mode}} active{{/ifEqual}}"
                    data-action="mode"
                    data-mode="{{mode}}"
                >{{label}}</a>
            </li>
        {{/each}}
        {{#if hiddenModeDataList.length}}
            <li class="divider"></li>
        {{/if}}
        {{#each scopeFilterDataList}}
            <li>
                <a
                    role="button"
                    tabindex="0"
                    data-action="toggleScopeFilter"
                    data-name="{{scope}}"
                >
                    <span class="fas fa-check filter-check-icon check-icon pull-right{{#if disabled}} hidden{{/if}}"></span>
                    <div>{{translate scope category='scopeNamesPlural'}}</div>

                </a>
            </li>
        {{/each}}
        {{#if hasMoreItems}}
            <li class="divider"></li>
        {{/if}}
        {{#if isCustomViewAvailable}}
            <li>
                <a
                    role="button"
                    tabindex="0"
                    data-action="createCustomView"
                >{{translate 'Create Shared View' scope='Calendar'}}</a>
            </li>
        {{/if}}
        {{#if hasWorkingTimeCalendarLink}}
            <li>
                <a href="#WorkingTimeCalendar">{{translate 'WorkingTimeCalendar' category='scopeNamesPlural'}}</a>
            </li>
        {{/if}}
    </ul>
</div>
