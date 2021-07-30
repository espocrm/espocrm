<div class="button-container">
    <div class="btn-group">
        <button type="button" class="btn btn-default" data-action="addField"><span class="fas fa-plus"></span> {{translate 'Add Field' scope='Admin'}}</button>
    </div>
</div>

<div class="margin-bottom-2x margin-top">
    <input
        type="text"
        maxlength="64"
        placeholder="{{translate 'Search'}}"
        data-name="quick-search"
        class="form-control"
    >
</div>

<table class="table fields-table">
    <thead>
        <th width="35%">{{translate 'Name' scope='FieldManager'}}</td>
        <th width="35%">{{translate 'Label' scope='FieldManager'}}</td>
        <th width="20%">{{translate 'Type' scope='FieldManager'}}</td>
        <th width="10%" align="right"></td>
    </thead>
    <tbody>
    {{#each fieldDefsArray}}
    <tr data-name="{{name}}" class="field-row">
        <td>
            <a
                href="#Admin/fieldManager/scope={{../scope}}&field={{name}}"
                class="field-link"
                data-scope="{{../scope}}"
                data-field="{{name}}"
            >{{name}}</a>
        </td>
        <td>{{translate name scope=../scope category='fields'}}</td>
        <td>{{translate type category='fieldTypes' scope='Admin'}}</td>
        <td align="right">{{#if isCustom}}<a href="javascript:" data-action="removeField" data-name="{{name}}">remove</a>{{/if}}</td>
    </tr>
    {{/each}}
    </tbody>
</table>

<div class="no-data hidden">{{translate 'No Data'}}</div>
