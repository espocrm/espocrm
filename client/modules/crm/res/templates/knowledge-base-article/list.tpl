<div class="page-header">{{{header}}}</div>
<div class="search-container">{{{search}}}</div>

<div class="row">
    {{#unless categoriesDisabled}}
    <div class="categories-container{{#unless categoriesDisabled}} col-md-3 col-sm-4{{else}} col-md-12{{/unless}}">{{{categories}}}</div>
    {{/unless}}
    <div class="list-container{{#unless categoriesDisabled}} col-md-9 col-sm-8{{else}} col-md-12{{/unless}}">{{{list}}}</div>
</div>

