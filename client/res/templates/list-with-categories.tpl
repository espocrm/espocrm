<div class="page-header">{{{header}}}</div>
<div class="search-container">{{{search}}}</div>

<div class="row">
    {{#unless categoriesDisabled}}
    <div class="{{#unless categoriesDisabled}} col-md-3 col-sm-4{{else}} col-md-12{{/unless}} list-categories-column">
        <div class="categories-container">{{{categories}}}</div>
    </div>
    {{/unless}}
    <div class="{{#unless categoriesDisabled}} col-md-9 col-sm-8{{else}} col-md-12{{/unless}} list-main-column">
        <div class="nested-categories-container{{#if isExpanded}} hidden{{/if}}">{{{nestedCategories}}}</div>
        <div class="list-container">{{{list}}}</div>
    </div>
</div>
