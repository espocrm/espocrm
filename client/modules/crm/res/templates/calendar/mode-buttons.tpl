<div class="btn-group" role="group">
    <button
        type="button"
        class="btn btn-text dropdown-toggle"
        data-toggle="dropdown"
    >{{modeLabel}}&nbsp;<span class="caret"></span></button>
    <ul class="dropdown-menu pull-right">
        {{#each modeDataList}}
            <li>
                <a
                    role="button"
                    tabindex="0"
                    class="{{#ifEqual mode ../mode}} active{{/ifEqual}}"
                    data-action="mode"
                    data-mode="{{mode}}"
                ><span class="item-text">{{label}}</span></a>
            </li>
        {{/each}}
        {{#if modeDataList.length}}
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
                    <span class="fas fa-check filter-check-icon check-icon pull-right{{#if disabled}} hidden {{/if}}"></span>
                    <div>
                        {{~#if iconClass~}}
                            <span class="item-icon {{iconClass}}"></span>
                        {{~/if~}}
                        <span class="item-text">{{translate scope category='scopeNamesPlural'}}</span>
                    </div>
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
                >
                    <span class="item-icon fas fa-plus"></span>
                    {{~null~}}
                    <span class="item-text">{{translate 'Create Shared View' scope='Calendar'}}</span>
                </a>
            </li>
        {{/if}}
        {{#if hasWorkingTimeCalendarLink}}
            <li>
                <a href="#WorkingTimeCalendar">
                    <span class="item-text">{{translate 'WorkingTimeCalendar' category='scopeNamesPlural'}}</span>
                </a>
            </li>
        {{/if}}
    </ul>
</div>
