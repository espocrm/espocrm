
<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='intSearchRanges'}}
</select>
<input type="text" class="form-control input-sm hidden" data-name="{{name}}" value="{{value}}" pattern="[\-]?[0-9]*" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}} placeholder="{{translate 'Value'}}" autocomplete="espo-{{name}}">
<input type="text" class="form-control{{#ifNotEqual searchType 'between'}} hidden{{/ifNotEqual}} additional input-sm" data-name="{{name}}-additional" value="{{value2}}" pattern="[\-]?[0-9]*" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}} placeholder="{{translate 'Value'}}" autocomplete="espo-{{name}}-additional">
