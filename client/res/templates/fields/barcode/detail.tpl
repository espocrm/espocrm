{{#if isNotEmpty}}

{{#if viewObject.isSvg}}
<svg class="barcode"></svg>
{{else}}
<div class="barcode"></div>
{{/if}}

{{else}}
{{#if valueIsSet}}{{{translate 'None'}}}{{else}}...{{/if}}
{{/if}}