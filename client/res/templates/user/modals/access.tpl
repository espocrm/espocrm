<div class="panel panel-default no-side-margin">
    <div class="panel-body">
        <div class="row">
            {{#each valuePermissionDataList}}
                <div class="cell col-sm-3 form-group" data-name="{{name}}">
                    <label class="control-label" data-name="{{name}}">{{translate name category="fields" scope="Role"}}</label>
                    <div class="field" data-name="{{name}}">
                        <span class="text-{{lookup ../styleMap value}}">
                            {{translateOption value scope="Role" field="assignmentPermission" translatedOptions=../levelListTranslation}}
                        </span>
                    </div>
                </div>
            {{/each}}
        </div>
    </div>
</div>

<div class="user-access-table no-side-margin">{{{table}}}</div>
