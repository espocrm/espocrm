
<select class="form-control search-type input-sm" name="{{name}}-type">
    {{options searchTypeList searchType field='intSearchRanges'}}
</select>
<input type="text" class="form-control input-sm hidden" name="{{name}}" value="{{searchParams.value1}}" pattern="[\-]?[0-9]*" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}} placeholder="{{translate 'Value'}}">
<input type="text" class="form-control{{#ifNotEqual searchParams.type 'between'}} hidden{{/ifNotEqual}} additional input-sm" name="{{name}}-additional" value="{{searchParams.value2}}" pattern="[\-]?[0-9]*" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}} placeholder="{{translate 'Value'}}">



