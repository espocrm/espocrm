<select data-name="{{name}}" class="main-element form-control input-sm">
	<option value="isTrue" {{#ifEqual searchType 'isTrue'}} selected{{/ifEqual}}>{{translate 'Yes'}}</option>
	<option value="isFalse" {{#ifEqual searchType 'isFalse'}} selected{{/ifEqual}}>{{translate 'No'}}</option>
	<option value="any" {{#ifEqual searchType 'any'}} selected{{/ifEqual}}>{{translateOption 'any' field='searchRanges'}}</option>
</select>
