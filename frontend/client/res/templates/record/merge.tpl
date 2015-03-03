
<div class="merge">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th width="20%"></th>
                {{#each models}}
                <th width="5%">
                    <input type="radio" name="check-all" value="{{prop this 'id'}}" data-id="{{id}}" class="pull-right">
                </th>
                <th width="{{../width}}%">
                    <a href="#{{../scope}}/view/{{prop this 'id'}}">{{get this 'name'}}</a>
                </th>
                {{/each}}
            </tr>
        </thead>
        <tbody>
            {{#each rows}}
            <tr>
                <td align="right">
                    {{translate name scope=../scope category='fields'}}
                </td>
                {{#each columns}}
                <td>
                    <input type="radio" name="{{../name}}" value="{{id}}" data-id="{{id}}" class="pull-right field-radio">
                </td>
                <td class="{{id}}">
                    <div class="field field-{{name}}">
                        {{{var fieldVariable ../../this}}}
                    </div>
                </td>
                {{/each}}
            </tr>
            {{/each}}
        </tbody>
    </table>
    <div class="button-container">
        <button class="btn btn-primary" data-action="merge">{{translate 'Merge'}}</button>
        <button class="btn btn-default" data-action="cancel">{{translate 'Cancel'}}</button>
    </div>
</div>

