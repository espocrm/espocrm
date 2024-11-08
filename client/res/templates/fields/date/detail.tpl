{{#if dateValue ~}}
    <span
        {{#if titleDateValue}}title="{{titleDateValue}}"{{/if}}
        class="{{#if style}} text-{{style}} {{/if}} {{#if useNumericFormat}} numeric-text {{/if}}"
    >{{dateValue}}</span>
{{~/if}}

{{#if isNone}}
<span class="none-value">{{translate 'None'}}</span>
{{/if}}

{{#if isLoading}}
<span class="loading-value"></span>
{{/if}}
