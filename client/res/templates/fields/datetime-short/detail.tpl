{{#if dateValue ~}}
    <span
        title="{{fullDateValue}}"
        {{#if style}}class="text-{{style}}"{{/if}}
    >{{dateValue}}</span>
{{~/if}}

{{#if isNone}}
<span class="none-value">{{translate 'None'}}</span>
{{/if}}

{{#if isLoading}}
<span class="loading-value"></span>
{{/if}}
