{{#if fieldDataList.length}}
<div class="panel">
    <table class="table table-bordered-inside">
        {{#if editAccess}}
            <tr>
                <th style="width: 20px"><input type="checkbox" class="checkbox-all form-checkbox"></th>
                <th style="width: 30%"></th>
                <th></th>
            </tr>
        {{/if}}
        {{#each fieldDataList}}
            <tr>
                {{#if ../editAccess}}<td>{{#if editAccess}}
                    <input type="checkbox" class="checkbox form-checkbox" data-name="{{name}}">{{/if}}</td>{{/if}}
                <td style="width: 30%">{{translate name category='fields' scope=../scope}}</td>
                <td>
                    <div class="field" data-name="{{name}}">{{{var key ../this}}}</div>
                </td>
            </tr>
        {{/each}}
    </table>
</div>
{{else}}
{{translate 'No Data'}}
{{/if}}
