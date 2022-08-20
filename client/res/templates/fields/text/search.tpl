<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='varcharSearchRanges'}}
</select>
<input
    type="text"
    class="main-element form-control input-sm"
    data-name="{{name}}"
    value="{{searchData.value}}"
    {{#if params.maxLength}}maxlength="{{params.maxLength}}"{{/if}}
    {{#if params.size}} size="{{params.size}}"{{/if}}
    autocomplete="espo-{{name}}"
    placeholder="{{translate 'Value'}}"
>
