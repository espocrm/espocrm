
{{#if topBar}}
<div class="list-buttons-container clearfix">
    {{#each buttonList}}
        {{button name scope=../../scope label=label style=style}}
    {{/each}}
</div>
{{/if}}

{{#unless rows.length}}
    {{#if createDisabled}}
        {{#unless showRoot}}
            {{translate 'No Data'}}
        {{/unless}}
    {{/if}}
{{/unless}}
<div class="list list-expanded">
    {{#if showRoot}}
    <span class="small text-primary glyphicon glyphicon-book"></span>
    <a href="#{{scope}}" class="action link{{#if rootIsSelected}} text-bold{{/if}}" data-action="selectRoot">{{rootName}}</a>
    {{/if}}
    {{#if showEditLink}}
    <a href="#{{scope}}" class="small pull-right" title="{{translate 'Manage Categories' scope=scope}}"><span class="glyphicon glyphicon-th-list"></span></a>
    {{/if}}

    <ul class="list-group list-group-tree list-group-no-border">
    {{#each rows}}
        {{{var this ../this}}}
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





