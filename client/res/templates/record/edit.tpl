<div class="edit" id="{{id}}" data-scope="{{scope}}">
    {{#unless buttonsDisabled}}
    <div class="detail-button-container button-container record-buttons clearfix">
        <div class="btn-group actions-btn-group" role="group">
        {{#each buttonList}}
            {{button name scope=../entityType label=label style=style html=html hidden=hidden className='btn-xs-wide'}}
        {{/each}}
        {{#if dropdownItemList}}
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <span class="fas fa-ellipsis-h"></span>
        </button>
        <ul class="dropdown-menu pull-left">
            {{#each dropdownItemList}}
            {{#if this}}
            <li
                class="{{#if hidden}}hidden{{/if}}"
            >
                <a
                    href="javascript:"
                    class="action"
                    data-action="{{name}}"
                >{{#if html}}{{{html}}}{{else}}{{translate label scope=../entityType}}{{/if}}</a>
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
            <div class="middle">{{{middle}}}</div>
            <div class="extra">{{{extra}}}</div>
            <div class="bottom">{{{bottom}}}</div>
        </div>
        <div class="side">
        {{{side}}}
        </div>
    </div>
</div>
