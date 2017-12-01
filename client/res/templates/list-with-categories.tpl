<div class="page-header">{{{header}}}</div>
<div class="search-container">{{{search}}}</div>

<div class="row">
    {{#unless categoriesDisabled}}
    <div class="{{#unless categoriesDisabled}} col-md-3 col-sm-4{{else}} col-md-12{{/unless}}">
        <div class="categories-container">{{{categories}}}</div>

        {{#if hasExpandedToggler}}
        <div class="expanded-toggler-container">
            <a href="javascript:" title="{{translate 'Collapse'}}" class="action{{#unless isExpanded}} hidden{{/unless}}" data-action="collapse"><span class="small glyphicon glyphicon-folder-open"></span></a>
            <a href="javascript:" title="{{translate 'Expand'}}" class="action{{#if isExpanded}} hidden{{/if}}" data-action="expand""><span class="small glyphicon glyphicon-folder-close"></span></a>
        </div>
        {{/if}}
    </div>
    {{/unless}}
    <div class="{{#unless categoriesDisabled}} col-md-9 col-sm-8{{else}} col-md-12{{/unless}}">
        <div class="nested-categories-container{{#if isExpanded}} hidden{{/if}}">{{{nestedCategories}}}</div>
        <div class="list-container">{{{list}}}</div>
    </div>
</div>
