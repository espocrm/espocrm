
<div class="merge">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th width="20%"></th>
                {{#each dataList}}
                <th width="5%">
                    <input type="radio" name="check-all" value="{{id}}" data-id="{{id}}" class="pull-right">
                </th>
                <th width="{{../width}}%">
                    <a href="#{{../scope}}/view/{{id}}" target="_BLANK">{{name}}</a>
                </th>
                {{/each}}
            </tr>
        </thead>
        <tbody>
            {{#if hasCreatedAt}}
            <tr>
                <td align="right">
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
                <td align="right">
                    {{translate name scope=../scope category='fields'}}
                </td>
                {{#each columns}}
                <td>
                    {{#unless isReadOnly}}
                    <input type="radio" name="{{../name}}" value="{{id}}" data-id="{{id}}" class="pull-right field-radio">
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
            <button class="btn btn-danger" data-action="merge">{{translate 'Merge'}}</button>
            <button class="btn btn-default" data-action="cancel">{{translate 'Cancel'}}</button>
        </div>
    </div>
</div>
