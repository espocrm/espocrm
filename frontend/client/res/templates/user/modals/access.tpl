<div class="row">
	<div class="cell cell-assignmentPermission  col-sm-6 form-group">
		<label class="control-label">{{translate 'assignmentPermission' category="fields" scope="Role"}}</label>
		<div class="field">
			{{translateOption assignmentPermission scope="Role" field="assignmentPermission" translatedOptions=levelListTranslation}}
		</div>
	</div>
</div>
<div class="row">
    <div class="cell cell-userPermission  col-sm-6 form-group">
        <label class="control-label">{{translate 'userPermission' category="fields" scope="Role"}}</label>
        <div class="field">
            {{translateOption userPermission scope="Role" field="userPermission" translatedOptions=levelListTranslation}}
        </div>
    </div>
</div>

<div class="user-access-table">{{{table}}}</div>