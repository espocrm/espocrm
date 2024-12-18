{{#if hasStickyBar}}
    <div class="sticked-bar list-sticky-bar hidden">
        {{#if displayActionsButtonGroup}}
            <div class="btn-group">
                <button
                    type="button"
                    class="btn btn-default btn-xs-wide dropdown-toggle actions-button hidden"
                    data-toggle="dropdown"
                >{{translate 'Actions'}} <span class="caret"></span>
                </button>
                <ul class="dropdown-menu actions-menu">
                    {{#each massActionDataList}}
                        {{#if this}}
                            <li {{#if hidden}}class="hidden"{{/if}}>
                                <a
                                    role="button"
                                    tabindex="0"
                                    data-action="{{name}}"
                                    class="mass-action"
                                >{{translate name category="massActions" scope=../scope}}</a>
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
            </div>
        {{/if}}

        {{#if hasPagination}}
            {{{paginationSticky}}}
        {{/if}}
    </div>
{{/if}}

{{#if topBar}}
    <div class="list-buttons-container clearfix">
        {{#if displayActionsButtonGroup}}
            <div class="btn-group actions">
                {{#if massActionDataList}}
                    <button
                        type="button"
                        class="btn btn-default btn-xs-wide dropdown-toggle actions-button hidden"
                        data-toggle="dropdown"
                    >{{translate 'Actions'}} <span class="caret"></span></button>
                {{/if}}
                {{#if buttonList.length}}
                    {{#each buttonList}}
                        {{button
                            name
                            scope=../scope
                            label=label
                            style=style
                            hidden=hidden
                            class='list-action-item'
                        }}
                    {{/each}}
                {{/if}}

                <div class="btn-group">
                    {{#if dropdownItemList.length}}
                        <button
                            type="button"
                            class="btn btn-text dropdown-toggle dropdown-item-list-button"
                            data-toggle="dropdown"
                        ><span class="fas fa-ellipsis-h"></span></button>
                        <ul class="dropdown-menu pull-left">
                            {{#each dropdownItemList}}
                                {{#if this}}
                                    <li class="{{#if hidden}}hidden{{/if}}">
                                        <a
                                            role="button"
                                            tabindex="0"
                                            class="action list-action-item"
                                            data-action="{{name}}"
                                            data-name="{{name}}"
                                        >{{#if html}}{{{html}}}{{else}}{{translate label scope=../entityType}}{{/if}}</a></li>
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

                {{#if massActionDataList}}
                    <ul class="dropdown-menu actions-menu">
                        {{#each massActionDataList}}
                            {{#if this}}
                                <li {{#if hidden}}class="hidden"{{/if}}>
                                    <a
                                        role="button"
                                        tabindex="0"
                                        data-action="{{name}}"
                                        class="mass-action"
                                    >{{translate name category="massActions" scope=../scope}}</a></li>
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
        {{/if}}

        {{#if hasPagination}}
            {{{pagination}}}
        {{/if}}

        {{#if settings}}
            <div class="settings-container pull-right">{{{settings}}}</div>
        {{/if}}

        {{#if displayTotalCount}}
            <div class="text-muted total-count">
        <span
            title="{{translate 'Total'}}"
            class="total-count-span"
        >{{totalCountFormatted}}</span>
            </div>
        {{/if}}
    </div>
{{/if}}



{{#if collectionLength}}
    <div
        class="list {{#if showMoreActive}} has-show-more {{/if}}"
        data-scope="{{scope}}"
        tabindex="-1"
    >
        <table
            class="table {{#if hasColumnResize~}} column-resizable {{~/if}}"
        >
            {{#if header}}
            <thead>
                <tr>
                    {{#if checkboxes}}
                    <th
                        style="width: {{checkboxColumnWidth}}"
                        data-name="r-checkbox"
                        class="checkbox-cell"
                    >
                        <span
                            class="select-all-container"
                        ><input type="checkbox" class="select-all form-checkbox form-checkbox-small"></span>
                        {{#unless checkAllResultDisabled}}
                        <div class="btn-group checkbox-dropdown">
                            <a
                                class="btn btn-link btn-sm dropdown-toggle"
                                data-toggle="dropdown"
                                tabindex="0"
                                role="button"
                            >
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a
                                        role="button"
                                        tabindex="0"
                                        data-action="selectAllResult"
                                    >{{translate 'Select All Results'}}</a>
                                </li>
                            </ul>
                        </div>
                        {{/unless}}
                    </th>
                    {{/if}}
                    {{#each headerDefs}}
                    <th
                        style="{{#if width}}width: {{width}};{{/if}}{{#if align}} text-align: {{align}};{{/if}}"
                        class="{{#if className~}} {{className}} {{~/if}} field-header-cell"
                        {{#if name}}data-name="{{name}}"{{/if}}
                    >
                        {{#if this.isSortable}}
                            <a
                                role="button"
                                tabindex="0"
                                class="sort"
                                data-name="{{this.name}}"
                                title="{{translate 'Sort'}}"
                            >{{label}}</a>
                            {{#if this.isSorted}}
                                {{#unless this.isDesc}}
                                <span class="fas fa-chevron-down fa-sm"></span>
                                {{else}}
                                <span class="fas fa-chevron-up fa-sm"></span>
                                {{/unless}}
                            {{/if}}
                        {{else}}
                            {{#if html}}
                            {{{html}}}
                            {{else}}
                            {{label}}
                            {{/if}}
                        {{/if}}

                        {{#if resizable}}
                            <div class="column-resizer {{#if resizeOnRight}} column-resizer-right {{/if}}"></div>
                        {{/if}}
                    </th>
                    {{/each}}
                </tr>
            </thead>
            {{/if}}
            <tbody>
            {{#each rowDataList}}
                <tr
                    data-id="{{id}}"
                    class="list-row {{#if isStarred}} starred {{~/if}}"
                >{{{var id ../this}}}</tr>
            {{/each}}
            </tbody>
        </table>

        {{#if showMoreEnabled}}
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
    </div>
{{else}}
    {{#unless noDataDisabled}}
    <div class="no-data">{{translate 'No Data'}}</div>
    {{/unless}}
{{/if}}
