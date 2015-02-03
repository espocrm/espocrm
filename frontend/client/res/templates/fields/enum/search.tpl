<select name="{{name}}" class="form-control input-sm" multiple>
    {{options params.options searchParams.value scope=scope field=name translatedOptions=translatedOptions}}
</select>

<input name="{{name}}" type="hidden">

