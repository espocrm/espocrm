
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
    {{#if showRootMenu}}
    <div class="btn-group pull-right">
        <a href="javascript:" class="small dropdown-toggle btn-link" data-toggle="dropdown">
            <span class="fas fa-ellipsis-h"></span>
        </a>
        <ul class="dropdown-menu">
            {{#if hasExpandedToggler}}
            <li class="{{#unless isExpanded}}hidden{{/unless}}">
                <a href="javascript:" class="category-expanded-toggle-link action" data-action="collapse">{{translate 'Collapse'}}</a>
            </li>
            <li class="{{#if isExpanded}}hidden{{/if}}">
                <a href="javascript:" class="category-expanded-toggle-link action" data-action="expand"">{{translate 'Expand'}}</a>
            </li>
            {{/if}}
            {{#if showEditLink}}
            <li>
                <a href="#{{scope}}" class="action manage-categories-link" data-action="manageCategories">{{translate 'Manage Categories' scope=scope}}</a>
            </li>
            {{/if}}
        </ul>
    </div>
    {{/if}}

    {{#if showRoot}}
    <div class="root-item">
    <a href="#{{scope}}" class="action link{{#if rootIsSelected}} text-bold{{/if}}" data-action="selectRoot">{{rootName}}{{#if isExpanded}} <span class="fas fa-level-down-alt fa-sm"></span>{{/if}}</a>
    </div>
    {{/if}}

    <ul class="list-group list-group-tree list-group-no-border">
    {{#each rowList}}
        <li data-id="{{./this}}" class="list-group-item">
        {{{var this ../this}}}
        </li>
    {{/each}}
    {{#unless createDisabled}}
    <li class="list-group-item">
        <div>
            <a href="javascript:" data-action="create" class="action small" title="{{translate 'Add'}}"><span class="fas fa-plus"></span></a>
        </div>
    </li>
    {{/unless}}
    </ul>
</div>
