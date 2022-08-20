<div class="detail" id="{{id}}" data-scope="{{scope}}" tabindex="-1">
    {{#unless buttonsDisabled}}
    <div class="detail-button-container button-container record-buttons clearfix">
        <div class="btn-group actions-btn-group" role="group">
            {{#each buttonList}}
                {{button name
                    scope=../entityType
                    label=label
                    style=style
                    hidden=hidden
                    html=html
                    title=title
                    text=text
                    className='btn-xs-wide'
                    disabled=disabled
                }}
            {{/each}}
            {{#if dropdownItemList}}
            <button
                type="button"
                class="btn btn-default dropdown-toggle dropdown-item-list-button{{#if dropdownItemListEmpty}} hidden{{/if}}"
                data-toggle="dropdown"
            ><span class="fas fa-ellipsis-h"></span></button>
            <ul class="dropdown-menu pull-left">
                {{#each dropdownItemList}}
                {{#if this}}
                <li
                    class="{{#if hidden}}hidden{{/if}}{{#if disabled}} disabled{{/if}}"
                ><a
                    role="button"
                    tabindex="0"
                    class="action"
                    data-action="{{name}}"
                    {{#if title}}title="{{title}}"{{/if}}
                    {{#each data}} data-{{@key}}="{{./this}}"{{/each}}
                >{{#if html}}{{{html}}}{{else}}{{#if text}}{{text}}{{else}}{{translate label scope=../entityType}}{{/if}}{{/if}}</a></li>
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
        {{#if navigateButtonsEnabled}}
        <div class="pull-right">
            <div class="btn-group" role="group">
                <button
                    type="button"
                    class="btn btn-text btn-icon action {{#unless previousButtonEnabled}} disabled{{/unless}}"
                    data-action="previous"
                    title="{{translate 'Previous Entry'}}"
                >
                    <span class="fas fa-chevron-left"></span>
                </button>
                <button
                    type="button"
                    class="btn btn-text btn-icon action {{#unless nextButtonEnabled}} disabled{{/unless}}"
                    data-action="next"
                    title="{{translate 'Next Entry'}}"
                >
                    <span class="fas fa-chevron-right"></span>
                </button>
            </div>
        </div>
        {{/if}}
    </div>
    <div class="detail-button-container button-container edit-buttons hidden clearfix">
        <div class="btn-group actions-btn-group" role="group">
        {{#each buttonEditList}}
        {{button name
            scope=../entityType
            label=label
            style=style
            hidden=hidden
            html=html
            title=title
            text=text
            className='btn-xs-wide'
            disabled=disabled
        }}
        {{/each}}
        {{#if dropdownEditItemList}}
        <button
            type="button"
            class="btn btn-default dropdown-toggle dropdown-edit-item-list-button{{#if dropdownEditItemListEmpty}} hidden{{/if}}"
            data-toggle="dropdown"
        ><span class="fas fa-ellipsis-h"></span></button>
        <ul class="dropdown-menu pull-left">
            {{#each dropdownEditItemList}}
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
