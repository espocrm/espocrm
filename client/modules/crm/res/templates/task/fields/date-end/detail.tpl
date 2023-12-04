{{#if dateValue}}<span{{#if isOverdue}} class="text-danger"{{/if}} title="{{dateValue}}">{{dateValue}}</span>{{/if}}

{{#if isNone}}
<span class="none-value">{{translate 'None'}}</span>
{{/if}}

{{#if isLoading}}
<span class="loading-value"></span>
{{/if}}
