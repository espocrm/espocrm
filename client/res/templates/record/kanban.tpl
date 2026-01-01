
{{#if topBar}}
<div class="list-buttons-container clearfix">
    {{#if displayTotalCount}}
        <div class="text-muted total-count">
            <span
                title="{{translate 'Total'}}"
                class="total-count-span"
            >{{totalCountFormatted}}</span>
        </div>
    {{/if}}

    {{#if settings}}
        <div class="settings-container pull-right">{{{settings}}}</div>
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

<div class="list-kanban-container">
<div class="list-kanban" data-scope="{{scope}}" style="min-width: {{minTableWidthPx}}px">
    <div class="kanban-head-container">
    <table class="kanban-head">
        <thead>
            <tr class="kanban-row">
                {{#each groupDataList}}
                    <th
                        data-name="{{name}}"
                        class="group-header{{#if style}} group-header-{{style}}{{/if}}"
                    >
                        <div>
                            <span class="kanban-group-label">{{label}}</span>
                            <a
                                role="button"
                                tabindex="0"
                                title="{{translate 'Create'}}"
                                class="create-button hidden"
                                data-action="createInGroup"
                                data-group="{{name}}"
                            >
                                <span class="fas fa-plus fa-sm"></span>
                            </a>
                        </div>
                    </th>
                {{/each}}
            </tr>
        </thead>
    </table>
    </div>
    <div class="kanban-columns-container">
    <table class="kanban-columns">
        {{#unless isEmptyList}}
        <tbody>
            <tr class="kanban-row">
                {{#each groupDataList}}
                <td class="group-column" data-name="{{name}}">
                    <div>
                        <div class="group-column-list" data-name="{{name}}">
                            {{#each dataList}}
                            <div class="item" data-id="{{id}}">{{{var key ../../this}}}</div>
                            {{/each}}
                        </div>
                        <div class="show-more">
                            <a data-action="groupShowMore" data-name="{{name}}" title="{{translate 'Show more'}}" class="{{#unless hasShowMore}}hidden {{/unless}}btn btn-link btn-sm"><span class="fas fa-ellipsis-h fa-sm"></span></a>
                        </div>
                    </div>
                </td>
                {{/each}}
            </tr>
        </tbody>
        {{/unless}}
    </table>
    </div>
</div>
</div>


{{#if isEmptyList}}{{#unless noDataDisabled}}
    <div class="margin-top no-data">
        {{translate 'No Data'}}
    </div>
{{/unless}}{{/if}}
