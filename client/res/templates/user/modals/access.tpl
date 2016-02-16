<div class="row">
	<div class="cell cell-assignment-permission col-sm-4 form-group">
		<label class="control-label">{{translate 'assignmentPermission' category="fields" scope="Role"}}</label>
		<div class="field">
			{{translateOption assignmentPermission scope="Role" field="assignmentPermission" translatedOptions=levelListTranslation}}
		</div>
	</div>
    <div class="cell cell-user-permission col-sm-4 form-group">
        <label class="control-label">{{translate 'userPermission' category="fields" scope="Role"}}</label>
        <div class="field">
            {{translateOption userPermission scope="Role" field="userPermission" translatedOptions=levelListTranslation}}
        </div>
    </div>
    <div class="cell cell-user-permission col-sm-4 form-group">
        <label class="control-label">{{translate 'portalPermission' category="fields" scope="Role"}}</label>
        <div class="field">
            {{translateOption portalPermission scope="Role" field="portalPermission" translatedOptions=levelListTranslation}}
        </div>
    </div>
</div>

<div class="user-access-table">{{{table}}}</div>