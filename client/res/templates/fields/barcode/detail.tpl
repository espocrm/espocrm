{{#if isNotEmpty}}
<svg class="barcode"></svg>

{{else}}
{{#if valueIsSet}}{{{translate 'None'}}}{{else}}...{{/if}}
{{/if}}