
<div class="row search-row">
    <div class="form-group{{#if isWide}} col-lg-7{{/if}} col-md-8 col-sm-9">
        <div class="input-group">
            <div class="input-group-btn left-dropdown{{#unless leftDropdown}} hidden{{/unless}}">
                <button type="button" class="btn btn-default dropdown-toggle filters-button" title="{{translate 'Filter'}}" data-toggle="dropdown" tabindex="-1">
                    <span class="filters-label"></span>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu pull-left filter-menu">

                    <li><a class="preset" tabindex="-1" href="javascript:" data-name="" data-action="selectPreset"><div>{{translate 'all' category='presetFilters' scope=entityType}}</div></a></li>
                    {{#each presetFilterList}}
                    <li><a class="preset" tabindex="-1" href="javascript:" data-name="{{name}}" data-action="selectPreset"><div>{{#if label}}{{label}}{{else}}{{translate name category='presetFilters' scope=../../entityType}}{{/if}}</div></a></li>
                    {{/each}}
                    <li class="divider preset-control hidden"></li>


                    <li class="preset-control remove-preset hidden"><a tabindex="-1" href="javascript:" data-action="removePreset">{{translate 'Remove Filter'}}</a></li>
                    <li class="preset-control save-preset hidden"><a tabindex="-1" href="javascript:" data-action="savePreset">{{translate 'Save Filter'}}</a></li>
                    {{#if boolFilterList.length}}
                        <li class="divider"></li>
                    {{/if}}

                    {{#each boolFilterList}}
                        <li class="checkbox"><label><input type="checkbox" data-role="boolFilterCheckbox" data-name="{{./this}}" {{#ifPropEquals ../bool this true}}checked{{/ifPropEquals}}> {{translate this scope=../entityType category='boolFilters'}}</label></li>
                    {{/each}}
                </ul>
            </div>
            {{#unless textFilterDisabled}}<input type="text" class="form-control text-filter" data-name="textFilter" value="{{textFilter}}" tabindex="1" autocomplete="espo-text-search">{{/unless}}
            <div class="input-group-btn">
                <button type="button" class="btn btn-primary search btn-icon btn-icon-x-wide" data-action="search" title="{{translate 'Search'}}">
                    <span class="fa fa-search"></span>
                </button>
                <button type="button" class="btn btn-text btn-icon-wide" data-action="reset" title="{{translate 'Reset'}}">
                    <span class="fas fa-redo-alt"></span>
                </button>
                <button type="button" class="btn btn-text btn-icon-wide dropdown-toggle add-filter-button" data-toggle="dropdown" tabindex="-1">
                    <span class="fas fa-ellipsis-v"></span>
                </button>
                <ul class="dropdown-menu pull-right filter-list">
                    <li class="dropdown-header">{{translate 'Add Field'}}</li>
                    {{#each advancedFields}}
                        <li data-name="{{name}}" class="{{#if checked}}hidden{{/if}}"><a href="javascript:" class="add-filter" data-action="addFilter" data-name="{{name}}">{{translate name scope=../entityType category='fields'}}</a></li>
                    {{/each}}
                </ul>
            </div>
        </div>
    </div>
    <div class="form-group{{#if isWide}} col-lg-5{{/if}} col-md-4 col-sm-3">
        {{#if hasViewModeSwitcher}}
        <div class="btn-group view-mode-switcher-buttons-group">
            {{#each viewModeDataList}}
            <button type="button" data-name="{{name}}" data-action="switchViewMode" class="btn btn-icon btn-icon btn-text{{#ifEqual name ../viewMode}} active{{/ifEqual}}" title="{{title}}"><span class="{{iconClass}}"></span></button>
            {{/each}}
        </div>
        {{/if}}
    </div>
</div>

<div class="advanced-filters-bar" style="margin-bottom: 12px;"></div>
<div class="advanced-filters hidden grid-auto-fill-sm">
{{#each filterDataList}}
    <div class="filter filter-{{name}}" data-name="{{name}}">
        {{{var key ../this}}}
    </div>
{{/each}}
</div>
