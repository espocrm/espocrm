<div class="edit" id="{{id}}" data-scope="{{scope}}" tabindex="-1">
    {{#unless buttonsDisabled}}
    <div class="detail-button-container button-container record-buttons clearfix">
        <div class="btn-group actions-btn-group" role="group">
        {{#each buttonList}}
            {{button
                name
                scope=../entityType
                label=label
                style=style
                html=html
                hidden=hidden
                title=title
                text=text
                className='btn-xs-wide'
                disabled=disabled
            }}
        {{/each}}
        {{#if dropdownItemList}}
        <button
            type="button"
            class="btn btn-default dropdown-toggle{{#if dropdownItemListEmpty}} hidden{{/if}}"
            data-toggle="dropdown"
        ><span class="fas fa-ellipsis-h"></span>
        </button>
        <ul class="dropdown-menu pull-left">
            {{#each dropdownItemList}}
            {{#if this}}
            <li
                class="{{#if hidden}}hidden{{/if}}{{#if disabled}} disabled{{/if}}"
            >
                <a
                    role="button"
                    tabindex="0"
                    class="action"
                    data-action="{{name}}"
                    {{#if title}}title="{{title}}"{{/if}}
                >{{#if html}}{{{html}}}{{else}}{{#if text}}{{text}}{{else}}{{translate label scope=../entityType}}{{/if}}{{/if}}</a></li>
            </li>
            {{else}}
                {{#unless @first}}
                {{#unless @last}}
                <li class="divider"></li>
                {{/unless}}
                {{/unless}}
            {{/if}}
            {{/each}}
        </ul>
        {{/if}}
        </div>
    </div>
    {{/unless}}

    <div class="record-grid{{#if isWide}} record-grid-wide{{/if}}{{#if isSmall}} record-grid-small{{/if}}">
        <div class="left">
            {{#if hasMiddleTabs}}
            <div class="tabs middle-tabs btn-group">
                {{#each middleTabDataList}}
                <button
                    class="btn btn-text btn-wide{{#if isActive}} active{{/if}}{{#if hidden}} hidden{{/if}}"
                    data-tab="{{@key}}"
                >{{label}}</button>
                {{/each}}
            </div>
            {{/if}}
            <div class="middle">{{{middle}}}</div>
            <div class="extra">{{{extra}}}</div>
            <div class="bottom">{{{bottom}}}</div>
        </div>
        <div class="side{{#if hasMiddleTabs}} tabs-margin{{/if}}">
        {{{side}}}
        </div>
    </div>
</div>
