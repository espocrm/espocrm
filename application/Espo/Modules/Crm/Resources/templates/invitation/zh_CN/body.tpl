<p>Subject: {{name}}</p>
<p>Start: {{#if isAllDay}}{{dateStartFull}}{{else}}{{dateStartFull}} ({{timeZone}}){{/if}}</p>
{{#if isUser}}
    {{#if description}}
    <p>{{{description}}}</p>
    {{/if}}
{{/if}}
<p>
<a href="{{acceptLink}}">Accept</a>, <a href="{{declineLink}}">Decline</a>, <a href="{{tentativeLink}}">Tentative</a>
</p>
{{#if isUser}}
<p><a href="{{recordUrl}}">View record</a></p>
{{/if}}
