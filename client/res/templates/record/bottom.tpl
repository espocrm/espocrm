{{#each panelList}}
    {{#if isRightAfterDelimiter}}
        <div class="panels-show-more-delimiter">
            <a role="button" tabindex="0" data-action="showMorePanels" title="{{translate 'Show more'}}">
                <span class="fas fa-ellipsis-h fa-lg"></span>
            </a>
        </div>
    {{/if}}
    {{#if isTabsBeginning}}
    <div class="tabs btn-group">
        {{#each ../tabDataList}}
        <button
            class="btn btn-text btn-wide{{#if isActive}} active{{/if}}{{#if hidden}} hidden{{/if}}"
            data-tab="{{@key}}"
        >{{label}}</button>
        {{/each}}
    </div>
    {{/if}}
    <div
        class="panel panel-{{#if style}}{{style}}{{else}}default{{/if}} panel-{{name}} headered{{#if hidden}} hidden{{/if}}{{#if sticked}} sticked{{/if}}{{#if tabHidden}} tab-hidden{{/if}}"
        data-name="{{name}}"
        data-style="{{#if style}}{{style}}{{/if}}"
        data-tab="{{tabNumber}}"
    >
        <div class="panel-heading">
            <div class="pull-right btn-group panel-actions-container">{{{var actionsViewKey ../this}}}</div>

            <h4 class="panel-title">
            {{#unless notRefreshable}}
            <span
                style="cursor: pointer; user-select: none;"
                class="action"
                title="{{translate 'clickToRefresh' category='messages'}}"
                data-action="refresh"
                data-panel="{{name}}"
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

        <div class="panel-body{{#if isForm}} panel-body-form{{/if}}" data-name="{{name}}">
            {{{var name ../this}}}
        </div>
    </div>
{{/each}}
