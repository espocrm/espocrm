<div class="button-container">
    <div class="btn-group">
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">{{translate 'Add Field' scope='Admin'}} <span class="caret"></span></button>
        <ul class="dropdown-menu">
            {{#each typeList}}
                <li><a href="javascript:" data-action="addField" data-scope="{{../scope}}" data-type="{{./this}}">{{translate this category='fieldTypes' scope='Admin'}}</a></li>
            {{/each}}
        </ul>
    </div>
</div>

<table class="table">
    <thead>
        <th width="35%">{{translate 'Name' scope='FieldManager'}}</td>
        <th width="35%">{{translate 'Label' scope='FieldManager'}}</td>
        <th width="20%">{{translate 'Type' scope='FieldManager'}}</td>
        <th width="10%" align="right"></td>
    </thead>
    <tbody>
    {{#each fieldDefsArray}}
    <tr>
        <td><a href="#Admin/fieldManager/scope={{../scope}}&field={{name}}" class="field-link" data-scope="{{../scope}}" data-field="{{name}}">{{name}}</td>
        <td>{{translate name scope=../scope category='fields'}}</td>
        <td>{{translate type category='fieldTypes' scope='Admin'}}</td>
        <td align="right">{{#if isCustom}}<a href="javascript:" data-action="removeField" data-name="{{name}}">remove</a>{{/if}}</td>
    </tr>
    {{/each}}
    </tbody>
</table>
