{{#each panelList}}
    {{#if isRightAfterDelimiter}}
        <div class="panels-show-more-delimiter">
            <a href="javascript:" data-action="showMorePanels" title="{{translate 'Show more'}}">
                <span class="fas fa-ellipsis-h fa-lg"></span>
            </a>
        </div>
    {{/if}}
<div
    class="panel panel-{{#if style}}{{style}}{{else}}default{{/if}} panel-{{name}}{{#if hidden}} hidden{{/if}}{{#if sticked}} sticked{{/if}}"
    data-name="{{name}}"
    data-name="{{name}}"
    data-style="{{#if style}}{{style}}{{/if}}"
>
    {{#if label}}
    <div class="panel-heading">
        <div class="pull-right btn-group panel-actions-container">{{{var actionsViewKey ../this}}}</div>

        <h4 class="panel-title">
            {{#unless notRefreshable}}
            <span
                style="cursor: pointer;"
                class="action"
                title="{{translate 'clickToRefresh' category='messages'}}"
                data-action="refresh" data-panel="{{name}}"
            >
            {{/unless}}
            {{#if titleHtml}}
                {{{titleHtml}}}
            {{else}}
                {{title}}
            {{/if}}
            {{#unless notRefreshable}}
            </span>
            {{/unless}}
        </h4>
    </div>
    {{/if}}

    <div class="panel-body{{#if isForm}} panel-body-form{{/if}}" data-name="{{name}}">
        {{{var name ../this}}}
    </div>
</div>
{{/each}}
