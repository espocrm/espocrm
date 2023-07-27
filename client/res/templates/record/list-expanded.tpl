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
        <button
            type="button"
            class="btn btn-default dropdown-toggle actions-button"
            data-toggle="dropdown"
            disabled
        >
            {{translate 'Actions'}}
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu">
            {{#each massActionList}}
            <li>
                <a
                    role="button"
                    tabindex="0"
                    data-action="{{./this}}"
                    class='mass-action'
                >{{translate this category="massActions" scope=../scope}}</a></li>
            {{/each}}
        </ul>
    </div>
    {{/if}}
    {{/if}}

    {{#each buttonList}}
        {{button
            name
            scope=../scope
            label=label
            style=style
            class='list-action-item'
        }}
    {{/each}}
</div>
{{/if}}

<div class="list list-expanded">
    <ul class="list-group">
    {{#each rowList}}
        <li data-id="{{./this}}" class="list-group-item list-row">
        {{{var this ../this}}}
        </li>
    {{/each}}
    </ul>

    {{#unless paginationEnabled}}
    {{#if showMoreEnabled}}
    {{#if showMoreActive}}
    <div class="show-more{{#unless showMoreActive}} hidden{{/unless}}">
        <a
            type="button"
            role="button"
            tabindex="0"
            class="btn btn-default btn-block"
            data-action="showMore"
            {{#if showCount}}title="{{translate 'Total'}}: {{totalCountFormatted}}"{{/if}}
        >
            {{#if showCount}}
            <div class="pull-right text-muted more-count">{{moreCountFormatted}}</div>
            {{/if}}
            <span>{{translate 'Show more'}}</span>
        </a>
    </div>
    {{/if}}
    {{/if}}
    {{/unless}}
</div>

{{#if bottomBar}}
<div>
{{#if paginationBottom}} {{{pagination}}} {{/if}}
</div>
{{/if}}

{{else}}
    {{#unless noDataDisabled}}
    <div class="no-data">{{translate 'No Data'}}</div>
    {{/unless}}
{{/if}}
