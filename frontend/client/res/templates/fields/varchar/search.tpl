
<select class="form-control search-type input-sm" name="{{name}}-type">
    {{options searchParams.typeOptions searchParams.type field='varcharSearchRanges'}}
</select>
<input type="text" class="main-element form-control input-sm" name="{{name}}" value="{{searchParams.value}}" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}{{#if params.size}} size="{{params.size}}"{{/if}} autocomplete="off" placeholder="{{translate 'Value'}}">

