{{#if isNotEmpty}}
<div class="complex-text">
    {{complexText displayValue}}
</div>
{{else}}
{{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
<span class="loading-value"></span>{{/if}}{{/if}}
