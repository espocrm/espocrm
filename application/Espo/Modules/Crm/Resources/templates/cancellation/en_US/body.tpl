<p>Subject: {{name}}</p>
<p>Start: {{#if isAllDay}}{{dateStartDate}}{{else}}{{dateStart}}{{/if}}</p>
{{#if isUser}}
<p><a href="{{recordUrl}}">View record</a></p>
{{/if}}
