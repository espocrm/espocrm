{{#if isNotEmpty}}

{{#if viewObject.isSvg}}
<svg class="barcode"></svg>
{{else}}
<div class="barcode"></div>
{{/if}}

{{else}}
{{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}<span class="loading-value">...</span>{{/if}}
{{/if}}
