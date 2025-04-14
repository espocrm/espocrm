<select
    data-name="{{name}}"
    class="form-control main-element {{#if nativeSelect}} native-select {{/if}}"
>
    {{options
        params.options value
        scope=scope
        field=name
        translatedOptions=translatedOptions
        includeMissingOption=true
        styleMap=styleMap
    }}
</select>
