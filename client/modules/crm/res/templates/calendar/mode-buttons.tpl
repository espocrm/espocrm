{{#each visibleModeDataList}}
<button class="btn btn-text strong{{#ifEqual mode ../mode}} active{{/ifEqual}}" data-action="mode" data-mode="{{mode}}" title="{{label}}"><span class="hidden-sm hidden-xs">{{label}}</span><span class="visible-sm visible-xs">{{labelShort}}</span></button>
{{/each}}
<div class="btn-group" role="group">
    <button type="button" class="btn btn-text dropdown-toggle" data-toggle="dropdown"><span class="fas fa-ellipsis-h"></span></button>
    <ul class="dropdown-menu pull-right">
        {{#each hiddenModeDataList}}
            <li>
                <a href="javascript:" class="{{#ifEqual mode ../mode}} active{{/ifEqual}}" data-action="mode" data-mode="{{mode}}">{{label}}</a>
            </li>
        {{/each}}
        {{#if hiddenModeDataList.length}}
            <li class="divider"></li>
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
