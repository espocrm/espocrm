
<div class="merge">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th style="width: 20%"></th>
                {{#each dataList}}
                <th style="vertical-align: middle; width: 5%">
                    <input
                        type="radio"
                        name="check-all"
                        value="{{id}}"
                        data-id="{{id}}"
                        class="pull-right form-radio"
                    >
                </th>
                <th style="width: {{../width}}%">
                    <a href="#{{../scope}}/view/{{id}}" target="_BLANK" class="text-large">{{name}}</a>
                </th>
                {{/each}}
            </tr>
        </thead>
        <tbody>
            {{#if hasCreatedAt}}
            <tr>
                <td style="text-align: right">
                    {{translate 'createdAt' scope=scope category='fields'}}
                </td>
                {{#each dataList}}
                <td></td>
                <td data-id="{{id}}">
                    <div class="field" data-name="createdAt">
                        {{{var createdAtViewName ../this}}}
                    </div>
                </td>
                {{/each}}
            </tr>
            {{/if}}
            {{#each rows}}
            <tr>
                <td style="text-align: right">
                    {{translate name scope=../scope category='fields'}}
                </td>
                {{#each columns}}
                <td>
                    {{#unless isReadOnly}}
                    <input
                        type="radio"
                        name="{{../name}}"
                        value="{{id}}"
                        data-id="{{id}}"
                        class="pull-right field-radio form-radio"
                    >
                    {{/unless}}
                </td>
                <td data-id="{{id}}">
                    <div class="field" data-name="{{../name}}">
                        {{{var fieldVariable ../../this}}}
                    </div>
                </td>
                {{/each}}
            </tr>
            {{/each}}
        </tbody>
    </table>
    <div class="button-container">
        <div class="btn-group">
            <button class="btn btn-danger btn-xs-wide" data-action="merge">{{translate 'Merge'}}</button>
            <button class="btn btn-default btn-xs-wide" data-action="cancel">{{translate 'Cancel'}}</button>
        </div>
    </div>
</div>
