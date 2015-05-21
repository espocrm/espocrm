{{#if collection.models.length}}
{{#if topBar}}
<div class="list-buttons-container clearfix">
    {{#if paginationTop}}
    <div>
        {{{pagination}}}
    </div>
    {{/if}}

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

<div class="list list-expanded">
    <ul class="list-group">
    {{#each rows}}
        {{{var this ../this}}}
    {{/each}}
    </ul>
    {{#unless paginationEnabled}}
    {{#if showMoreEnabled}}
    <div class="show-more{{#unless showMoreActive}} hide{{/unless}}">
        <a type="button" href="javascript:" class="btn btn-default btn-block"  data-action="showMore" {{#if showCount}}title="{{translate 'Total'}}: {{collection.total}}"{{/if}}>
            {{#if showCount}}
            <div class="pull-right text-muted more-count">{{moreCount}}</div>
            {{/if}}
            <span>{{translate 'Show more'}}</span>
        </a>
    </div>
    {{/if}}
    {{/unless}}
</div>

{{#if bottomBar}}
<div>
{{#if paginationBottom}} {{{pagination}}} {{/if}}
</div>
{{/if}}

{{else}}
    {{translate 'No Data'}}
{{/if}}
