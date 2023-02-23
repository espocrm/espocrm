<div class="page-header">{{{header}}}</div>
<div class="search-container">{{{search}}}</div>

{{#unless fallback}}
<div class="row">
    {{#if hasTree}}
    <div class="{{#if hasTree}} col-md-3 col-sm-4{{else}} col-md-12{{/if}} list-categories-column">
        <div class="categories-container">{{{categories}}}</div>
    </div>
    {{/if}}
    <div class="{{#if hasTree}} col-md-9 col-sm-8{{else}} col-md-12{{/if}} list-main-column">
        <div class="nested-categories-container{{#unless hasNestedCategories}} hidden{{/unless}}">{{{nestedCategories}}}</div>
        <div class="list-container">{{{list}}}</div>
    </div>
</div>
{{else}}
<div class="list-container">{{{list}}}</div>
{{/unless}}
