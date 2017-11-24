<div class="row">
    {{#each valuePermissionDataList}}
        <div class="cell col-sm-3 form-group" data-name="{{name}}">
            <label class="control-label" data-name="{{name}}">{{translate name category="fields" scope="Role"}}</label>
            <div class="field" data-name="{{name}}">
                {{translateOption value scope="Role" field="assignmentPermission" translatedOptions=levelListTranslation}}
            </div>
        </div>
    {{/each}}
</div>

<div class="user-access-table">{{{table}}}</div>