<div class="page-header">
    <h4>{{translate scope category='scopeNames'}}</h4>
</div>

{{#unless categoryList.length}}
    {{translate 'No Data'}}
{{else}}
    <div class="button-container">
        <div class="btn-group">
            <button class="btn btn-primary btn-xs-wide" data-action="save">{{translate 'Save'}}</button>
            <button class="btn btn-default btn-xs-wide" data-action="cancel">{{translate 'Cancel'}}</button>
        </div>
    </div>

    <div class="button-container negate-no-side-margin">
        <input
            type="text"
            maxlength="64"
            placeholder="{{translate 'Search'}}"
            data-name="quick-search"
            class="form-control"
            spellcheck="false"
        >
    </div>
{{/unless}}

{{#each categoryList}}
<div class="panel panel-default category-panel" data-name="{{./this}}" style="overflow: hidden;">
    <div class="panel-heading clearfix">
        <div
            class="pull-left"
            style="
                margin-right: 10px;
                padding-top: calc((var(--panel-heading-height) - var(--panel-heading-font-size)) / 2 - 1px);
            "
        >
            <a
                role="button"
                tabindex="0"
                data-action="showCategory"
                data-name="{{./this}}"
                class="action"
            ><span class="fas fa-chevron-down"></span></a>
            <a
                role="button"
                tabindex="0"
                data-action="hideCategory"
                data-name="{{./this}}"
                class="hidden action"
            ><span class="fas fa-chevron-up"></span></a>
        </div>
        <h4 class="panel-title">
            <span class="action" style="cursor: pointer;" data-action="toggleCategory" data-name="{{./this}}">
            {{translate this}}
            </span>
        </h4>
    </div>
    <div class="panel-body hidden" data-name="{{./this}}">{{{var this ../this}}}</div>
</div>
{{/each}}
<div class="no-data hidden">{{translate 'No Data'}}</div>
