
<div class="row search-row">
    <div class="form-group col-md-6 col-sm-8">
        <div class="input-group">
            <div class="input-group-btn left-dropdown{{#unless leftDropdown}} hidden{{/unless}}">
                <button type="button" class="btn btn-default dropdown-toggle filters-button" title="{{translate 'Filter'}}" data-toggle="dropdown" tabindex="-1">
                    <span class="filters-label"></span>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu pull-left filter-menu">

                    <li><a class="preset" tabindex="-1" href="javascript:" data-name="" data-action="selectPreset"><div>{{translate 'All'}}</div></a></li>
                    {{#each presetFilterList}}
                    <li><a class="preset" tabindex="-1" href="javascript:" data-name="{{name}}" data-action="selectPreset"><div>{{#if label}}{{label}}{{else}}{{translate name category='presetFilters' scope=../../scope}}{{/if}}</div></a></li>
                    {{/each}}
                    <li class="divider preset-control hidden"></li>


                    <li class="preset-control remove-preset hidden"><a tabindex="-1" href="javascript:" data-action="removePreset">{{translate 'Remove Filter'}}</a></li>
                    <li class="preset-control save-preset hidden"><a tabindex="-1" href="javascript:" data-action="savePreset">{{translate 'Save Filter'}}</a></li>
                    {{#if boolFilterList.length}}
                        <li class="divider"></li>
                    {{/if}}

                    {{#each boolFilterList}}
                        <li class="checkbox"><label><input type="checkbox" data-role="boolFilterCheckbox" name="{{./this}}" {{#ifPropEquals ../bool this true}}checked{{/ifPropEquals}}> {{translate this scope=../scope category='boolFilters'}}</label></li>
                    {{/each}}
                </ul>
            </div>
            <input type="text" class="form-control text-filter" name="textFilter" value="{{textFilter}}" tabindex="1">
            <div class="input-group-btn">
                <button type="button" class="btn btn-primary search btn-icon" data-action="search">
                    <span class="glyphicon glyphicon-search"></span>
                </button>
            </div>
        </div>
    </div>
    <div class="form-group col-md-6 col-sm-4">
        <div class="btn-group">
            <button type="button" class="btn btn-default" data-action="reset">
                <span class="glyphicon glyphicon-repeat"></span>&nbsp;{{translate 'Reset'}}
            </button>
            <button type="button" class="btn btn-default dropdown-toggle add-filter-button" data-toggle="dropdown" tabindex="-1">
                {{translate 'Add Field'}} <span class="caret"></span>
            </button>
            <ul class="dropdown-menu pull-right filter-list">
                {{#each advancedFields}}
                    <li data-name="{{name}}" class="{{#if checked}}hide{{/if}}"><a href="javascript:" class="add-filter" data-action="addFilter" data-name="{{name}}">{{translate name scope=../scope category='fields'}}</a></li>
                {{/each}}
            </ul>
        </div>
    </div>
</div>

<div class="advanced-filters-bar" style="margin-bottom: 12px;"></div>
<div class="row advanced-filters hidden">
{{#each filterList}}
    <div class="filter {{./this}} col-sm-4 col-md-3">
        {{{var this ../this}}}
    </div>
{{/each}}
</div>

