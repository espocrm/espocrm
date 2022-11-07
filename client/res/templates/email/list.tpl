<div class="page-header">{{{header}}}</div>
<div class="search-container">{{{search}}}</div>

<div class="row">
    {{#unless foldersDisabled}}
    <div class="left-container{{#unless foldersDisabled}} col-md-2 col-sm-3{{else}} col-md-12{{/unless}}">
        <div class="folders-container">{{{folders}}}</div>
    </div>
    {{/unless}}
    <div class="list-container{{#unless foldersDisabled}} col-md-10 col-sm-9{{else}} col-md-12{{/unless}}">{{{list}}}</div>
</div>
