{{#if collection.models.length}}
    {{#if hasStickyBar}}
        <div class="list-sticky-bar sticked-bar hidden">
            {{#if hasPagination}}
                {{{paginationSticky}}}
            {{/if}}
        </div>
    {{/if}}

    {{#if topBar}}
        <div class="list-buttons-container clearfix">
            {{#if checkboxes}}{{#if massActionDataList}}
                <div class="btn-group actions">
                    <button
                        type="button"
                        class="btn btn-default dropdown-toggle actions-button"
                        data-toggle="dropdown"
                        disabled
                    >{{translate 'Actions'}} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        {{#each massActionDataList}}
                            <li {{#if hidden}}class="hidden"{{/if}}>
                                <a
                                    role="button"
                                    tabindex="0"
                                    data-action="{{name}}"
                                    class="mass-action"
                                >{{translate name category="massActions" scope=../scope}}</a>
                            </li>
                        {{/each}}
                    </ul>
                </div>
            {{/if}}{{/if}}

            {{#each buttonList}}
                {{button
                    name
                    scope=../scope
                    label=label
                    style=style
                    class='list-action-item'
                }}
            {{/each}}

            {{#if hasPagination}}
                {{{pagination}}}
            {{/if}}
        </div>
    {{/if}}

    <div class="list list-expanded">
        <ul class="list-group">
        {{#each rowDataList}}
            <li
                data-id="{{id}}"
                class="list-group-item list-row {{#if isStarred}} starred {{~/if}}"
            >{{{var id ../this}}}</li>
        {{/each}}
        </ul>

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
    </div>
{{else}}
    {{#unless noDataDisabled}}
        <div class="no-data">{{translate 'No Data'}}</div>
    {{/unless}}
{{/if}}
