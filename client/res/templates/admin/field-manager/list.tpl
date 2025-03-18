<div class="button-container">
    <div class="btn-group">
        {{#if hasAddField}}
        <button
            type="button"
            class="btn btn-default btn-wide"
            data-action="addField"
        ><span class="fas fa-plus fa-sm"></span><span>{{translate 'Add Field' scope='Admin'}}</span></button>
        {{/if}}
    </div>
</div>

<div class="margin-bottom-2x margin-top">
    <input
        type="text"
        maxlength="64"
        placeholder="{{translate 'Search'}}"
        data-name="quick-search"
        class="form-control"
        spellcheck="false"
    >
</div>

<table class="table fields-table table-panel table-hover">
    <thead>
        <th style="width: 35%">{{translate 'Label' scope='FieldManager'}}</th>
        <th style="width: 35%">{{translate 'Name' scope='FieldManager'}}</th>
        <th style="width: 20%">{{translate 'Type' scope='FieldManager'}}</th>
        <th style="width: 8%; text-align: right;"></th>
    </thead>
    <tbody>
    {{#each fieldDefsArray}}
    <tr data-name="{{name}}" class="field-row">
        <td>
            {{#if isEditable}}
                <a
                    href="#Admin/fieldManager/scope={{../scope}}&field={{name}}"
                    class="field-link"
                    data-scope="{{../scope}}"
                    data-field="{{name}}"
                >{{translate name scope=../scope category='fields'}}</a>
            {{else}}
                {{translate name scope=../scope category='fields'}}
            {{/if}}
        </td>
        <td>
            <span class="{{#if isCustom}} text-info {{/if}}">{{name}}</span>

        </td>
        <td>{{translate type category='fieldTypes' scope='Admin'}}</td>
        <td style="text-align: right">
            <div class="btn-group row-dropdown-group">
                <button
                    class="btn btn-link btn-sm dropdown-toggle"
                    data-toggle="dropdown"
                ><span class="caret"></span></button>
                <ul class="dropdown-menu pull-right">
                    <li>
                        <a
                            role="button"
                            tabindex="0"
                            data-action="viewDetails"
                            data-name="{{name}}"
                        >{{translate 'View Details' scope='FieldManager'}}</a>
                    </li>
                    {{#if isCustom}}
                        <li class="divider"></li>
                        <li>
                            <a
                                role="button"
                                tabindex="0"
                                data-action="removeField"
                                data-name="{{name}}"
                            >{{translate 'Remove'}}</a>
                        </li>
                    {{/if}}
                </ul>
            </div>
        </td>
    </tr>
    {{/each}}
    </tbody>
</table>

<div class="no-data hidden">{{translate 'No Data'}}</div>
