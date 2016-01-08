<div class="page-header">{{{header}}}</div>
<div class="search-container">{{{search}}}</div>

<div class="row">
    {{#unless foldersDisabled}}
    <div class="folders-container{{#unless foldersDisabled}} col-md-3 col-sm-4{{else}} col-md-12{{/unless}}">{{{folders}}}</div>
    {{/unless}}
    <div class="list-container{{#unless foldersDisabled}} col-md-9 col-sm-8{{else}} col-md-12{{/unless}}">{{{list}}}</div>
</div>

