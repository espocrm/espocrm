<p>Subject: {{name}}</p>
<p>Start: {{#if isAllDay}}{{dateStartFull}}{{else}}{{dateStartFull}} ({{timeZone}}){{/if}}</p>
{{#if isUser}}
<p><a href="{{recordUrl}}">View record</a></p>
{{/if}}
