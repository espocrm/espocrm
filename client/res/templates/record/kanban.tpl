
{{#if topBar}}
<div class="list-buttons-container clearfix">
    {{#if displayTotalCount}}
        <div class="text-muted total-count">
        {{translate 'Total'}}: <span class="total-count-span">{{totalCountFormatted}}</span>
        </div>
    {{/if}}

    {{#each buttonList}}
        {{button name scope=../scope label=label style=style}}
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
                    class="group-header{{#if style}} group-header-{{style}}{{/if}}{{#if nextStyle}} group-header-before-{{nextStyle}}{{/if}}"
                >
                    <div>
                        <span class="kanban-group-label">{{label}}</span>
                        {{#if ../isCreatable}}
                        <a
                            href="javascript:"
                            title="{{translate 'Create'}}"
                            class="create-button hidden"
                            data-action="createInGroup"
                            data-group="{{name}}"
                        >
                            <span class="fas fa-plus fa-sm"></span>
                        </a>
                        {{/if}}
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


{{#if isEmptyList}}
<div class="margin-top">
{{translate 'No Data'}}
</div>
{{/if}}
