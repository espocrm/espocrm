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
        <button type="button" class="btn btn-default dropdown-toggle actions-button" data-toggle="dropdown" disabled>
        {{translate 'Actions'}}
        <span class="caret"></span>
        </button>
        <ul class="dropdown-menu">
            {{#each massActionList}}
            <li><a href="javascript:" data-action="{{./this}}" class='mass-action'>{{translate this category="massActions" scope=../scope}}</a></li>
            {{/each}}
        </ul>
    </div>
    {{/if}}
    {{/if}}

    {{#if displayTotalCount}}
        <div class="text-muted total-count">
        {{translate 'Total'}}: {{collection.total}}
        </div>
    {{/if}}

    {{#each buttonList}}
        {{button name scope=../../scope label=label style=style}}
    {{/each}}
</div>
{{/if}}

<div class="list">
    <table class="table">
        {{#if header}}
        <thead>
            <tr>
                {{#if checkboxes}}
                <th width="40">
                    <input type="checkbox" class="select-all">
                    {{#unless checkAllResultDisabled}}
                    <div class="btn-group checkbox-dropdown">
                        <a class="btn btn-link btn-sm dropdown-toggle" data-toggle="dropdown">
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="javascript:" data-action="selectAllResult">{{translate 'Select All Result'}}</a></li>
                        </ul>
                    </div>
                    {{/unless}}
                </th>
                {{/if}}
                {{#each headerDefs}}
                <th {{#if width}} width="{{width}}"{{/if}}{{#if align}} style="text-align: {{align}};"{{/if}}>
                    {{#if this.sortable}}
                        <a href="javascript:" class="sort" data-name="{{this.name}}">
                        {{#if this.hasCustomLabel}}
                            {{this.customLabel}}
                        {{else}}
                            {{translate this.name scope=../../../collection.name category='fields'}}
                        {{/if}}
                        </a>
                        {{#if this.sorted}}{{#if this.asc}}<span class="caret"></span>{{else}}<span class="caret-up"></span>{{/if}}{{/if}}
                    {{else}}
                        {{#if this.hasCustomLabel}}
                            {{this.customLabel}}
                        {{else}}
                            {{translate this.name scope=../../../collection.name category='fields'}}
                        {{/if}}
                    {{/if}}
                </th>
                {{/each}}
            </tr>
        </thead>
        {{/if}}
        <tbody>
        {{#each rows}}
            {{{var this ../this}}}
        {{/each}}
        </tbody>
    </table>
    {{#unless paginationEnabled}}
    {{#if showMoreEnabled}}
    <div class="show-more{{#unless showMoreActive}} hide{{/unless}}">
        <a type="button" href="javascript:" class="btn btn-default btn-block" data-action="showMore" {{#if showCount}}title="{{translate 'Total'}}: {{collection.total}}"{{/if}}>
            {{#if showCount}}
            <div class="pull-right text-muted more-count">{{moreCount}}</div>
            {{/if}}
            <span>{{translate 'Show more'}}</span>
        </a>
    </div>
    {{/if}}
    {{/unless}}
</div>

{{#if bottomBar}}
<div>
{{#if paginationBottom}} {{{pagination}}} {{/if}}
</div>
{{/if}}

{{else}}
    {{translate 'No Data'}}
{{/if}}
