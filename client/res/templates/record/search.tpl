
<div class="row search-row">
    <div class="form-group{{#if isWide}} col-lg-7{{/if}} col-md-8 col-sm-9">
        <div class="input-group">
            <div class="input-group-btn left-dropdown{{#unless leftDropdown}} hidden{{/unless}}">
                <button
                    type="button"
                    class="btn btn-default dropdown-toggle filters-button"
                    title="{{translate 'Filter'}}"
                    data-toggle="dropdown"
                    tabindex="0"
                >
                    <span class="filters-label"></span>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu pull-left filter-menu">
                    {{#unless primaryFiltersDisabled}}
                        <li>
                            <a
                                class="preset"
                                tabindex="0"
                                role="button"
                                data-name=""
                                data-action="selectPreset"
                            ><div>{{translate 'all' category='presetFilters' scope=entityType}}</div></a>
                        </li>
                        {{#each presetFilterList}}
                        <li>
                            <a
                                class="preset"
                                tabindex="0"
                                role="button"
                                data-name="{{name}}"
                                data-action="selectPreset"
                            >
                                <div class="{{#if style}}text-{{style}}{{/if}}">
                                {{#if label}}{{label}}{{else}}{{translate name category='presetFilters' scope=../entityType}}{{/if}}
                                </div>
                            </a>
                        </li>
                        {{/each}}
                        <li class="divider preset-control hidden"></li>

                        <li class="preset-control remove-preset hidden">
                            <a tabindex="0" role="button" data-action="removePreset">{{translate 'Remove Filter'}}</a>
                        </li>
                        <li class="preset-control save-preset hidden">
                            <a tabindex="0" role="button" data-action="savePreset">{{translate 'Save Filter'}}</a>
                        </li>

                        {{#if boolFilterList.length}}
                            <li class="divider"></li>
                        {{/if}}
                    {{/unless}}

                    {{#each boolFilterList}}
                        <li class="checkbox">
                            <label>
                                <input
                                    type="checkbox"
                                    data-role="boolFilterCheckbox"
                                    data-name="{{./this}}"
                                    class="form-checkbox form-checkbox-small"
                                    {{#ifPropEquals ../bool this true}}checked{{/ifPropEquals}}
                                > {{translate this scope=../entityType category='boolFilters'}}
                            </label></li>
                    {{/each}}
                </ul>
            </div>
            <input
                type="search"
                class="form-control text-filter"
                data-name="textFilter"
                value="{{textFilter}}"
                tabindex="0"
                autocomplete="espo-text-search"
                spellcheck="false"
                {{#if textFilterDisabled}}disabled="disabled"{{/if}}
            >
            <div class="input-group-btn">
                <button
                    type="button"
                    class="btn btn-default search btn-icon btn-icon-x-wide"
                    data-action="search"
                    tabindex="0"
                    title="{{translate 'Search'}}"
                >
                    <span class="fas fa-search"></span>
                </button>
            </div>
            <div class="input-group-btn">
                <button
                    type="button"
                    class="btn btn-text btn-icon btn-icon-wide dropdown-toggle add-filter-button"
                    data-toggle="dropdown"
                    tabindex="0"
                >
                    <span class="fas fa-ellipsis-v"></span>
                </button>
                <button
                    type="button"
                    class="btn btn-text btn-icon btn-icon-wide"
                    data-action="reset"
                    title="{{translate 'Reset'}}"
                    tabindex="0"
                    style="visibility: hidden;"
                >
                    <span class="fas fa-times"></span>
                </button>
                <ul class="dropdown-menu pull-right filter-list">
                    <li class="dropdown-header">{{translate 'Add Field'}}</li>
                    {{#if hasFieldQuickSearch}}
                    <li class="quick-search-list-item">
                        <input class="form-control field-filter-quick-search-input">
                    </li>
                    {{/if}}
                    {{#each filterFieldDataList}}
                        <li
                            data-name="{{name}}"
                            class="filter-item {{#if checked}} hidden{{/if}}"
                        ><a
                            role="button"
                            tabindex="0"
                            class="add-filter"
                            data-action="addFilter"
                            data-name="{{name}}"
                        >{{label}}</a></li>
                    {{/each}}
                </ul>
            </div>
        </div>
    </div>
    <div class="form-group{{#if isWide}} col-lg-5{{/if}} col-md-4 col-sm-3">
        {{#if hasViewModeSwitcher}}
        <div class="btn-group view-mode-switcher-buttons-group">
            {{#each viewModeDataList}}
            <button
                type="button"
                data-name="{{name}}"
                data-action="switchViewMode"
                class="btn btn-icon btn-text{{#ifEqual name ../viewMode}} active{{/ifEqual}}"
                tabindex="0"
                title="{{title}}"
            ><span class="{{iconClass}}"></span></button>
            {{/each}}
        </div>
        {{/if}}
    </div>
</div>

<div class="advanced-filters hidden grid-auto-fill-sm">
{{#each filterDataList}}
    <div class="filter filter-{{name}}" data-name="{{name}}">
        {{{var key ../this}}}
    </div>
{{/each}}
</div>

<div class="advanced-filters-apply-container{{#unless toShowApplyFiltersButton}} hidden{{/unless}}">
    <a role="button" tabindex="0" class="btn btn-default btn-sm" data-action="applyFilters">
        <span class="fas fa-search fa-sm"></span>
        <span class="text-apply{{#if toShowResetFiltersText}} hidden{{/if}}">{{translate 'Apply'}}</span>
        <span class="text-reset{{#unless toShowResetFiltersText}} hidden{{/unless}}">{{translate 'Reset'}}</span>
    </a>
</div>
