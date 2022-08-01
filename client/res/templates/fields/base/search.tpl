<input
    type="text"
    class="main-element form-control input-sm"
    data-name="{{name}}"
    value="{{searchParams.value}}" {{#if params.maxLength}}
    maxlength="{{params.maxLength}}"{{/if}}{{#if params.size}}
    size="{{params.size}}"{{/if}}
    autocomplete="espo-{{name}}"
    {{#if noSpellCheck}}
    spellcheck="false"
    {{/if}}
>
