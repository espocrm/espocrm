
{{#if topBar}}
<div class="list-buttons-container clearfix">
    {{#if checkboxes}}
    {{#if massActionList}}
    <div class="btn-group actions">
        <button type="button" class="btn btn-default btn-sm dropdown-toggle actions-button" data-toggle="dropdown" disabled>
            &nbsp;<span class="glyphicon glyphicon-list"></span>&nbsp;
        </button>
        <ul class="dropdown-menu">
            {{#each massActionList}}
            <li><a href="javascript:" data-action="{{./this}}" class='mass-action'>{{translate this category="massActions" scope=../scope}}</a></li>
            {{/each}}
        </ul>
    </div>
    {{/if}}
    {{/if}}

    {{#each buttonList}}
        {{button name scope=../../scope label=label style=style}}
    {{/each}}
</div>
{{/if}}

{{#unless rows.length}}
    {{#if createDisabled}}
        {{translate 'No Data'}}
    {{/if}}
{{/unless}}
<div class="list list-expanded">
    {{#if showRoot}}
    <span class="small text-primary glyphicon glyphicon-book"></span>
    <a href="javascript:" class="action link{{#if rootIsSelected}} text-bold{{/if}}" data-action="selectRoot">{{rootName}}</a>
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




