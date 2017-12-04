
{{#if topBar}}
<div class="list-buttons-container clearfix">
    {{#each buttonList}}
        {{button name scope=../../scope label=label style=style}}
    {{/each}}
</div>
{{/if}}

{{#unless rowList.length}}
    {{#if createDisabled}}
        {{#unless showRoot}}
            {{translate 'No Data'}}
        {{/unless}}
    {{/if}}
{{/unless}}
<div class="list list-expanded">
    {{#if showEditLink}}
    <a href="#{{scope}}" class="small pull-right action manage-categories-link" data-action="manageCategories" title="{{translate 'Manage Categories' scope=scope}}"><span class="glyphicon glyphicon-th-list"></span></a>
    {{/if}}

    {{#if hasExpandedToggler}}
        <a href="javascript:" title="{{translate 'Collapse'}}" class="small pull-right category-expanded-toggle-link action{{#unless isExpanded}} hidden{{/unless}}" data-action="collapse"><span class="glyphicon glyphicon-folder-open"></span></a>
        <a href="javascript:" title="{{translate 'Expand'}}" class="small pull-right category-expanded-toggle-link action{{#if isExpanded}} hidden{{/if}}" data-action="expand""><span class="glyphicon glyphicon-folder-close"></span></a>
    {{/if}}

    {{#if showRoot}}
    <span class="small text-primary glyphicon glyphicon-book"></span>
    <a href="#{{scope}}" class="action link{{#if rootIsSelected}} text-bold{{/if}}" data-action="selectRoot">{{rootName}}</a>
    {{/if}}

    <ul class="list-group list-group-tree list-group-no-border">
    {{#each rowList}}
        <li data-id="{{./this}}" class="list-group-item">
        {{{var this ../this}}}
        </li>
    {{/each}}
    {{#unless createDisabled}}
    <li class="list-group-item">
        <div style="margin-left: 2px;">
            <a href="javascript:" data-action="create" class="action small" title="{{translate 'Add'}}"><span class="glyphicon glyphicon-plus"></span></a>
        </div>
    </li>
    {{/unless}}
    </ul>
</div>





