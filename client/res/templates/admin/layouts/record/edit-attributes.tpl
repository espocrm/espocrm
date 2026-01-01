{{#each attributeDataList}}
<div class="row">
    <div
        class="cell form-group {{#if isWide}}col-md-12{{else}}col-md-6{{/if}}"
        data-name="{{name}}"
    >
        <label
            class="control-label"
            data-name="{{name}}"
        >{{label}}</label>
        <div
            class="field"
            data-name="{{name}}"
        >{{{var viewKey ../this}}}</div>
    </div>
</div>
{{/each}}
