<p>Betreff: {{name}}</p>
<p>Beginn: {{#if isAllDay}}{{dateStartDate}}{{else}}{{dateStart}}{{/if}}</p>
{{#if isUser}}
    {{#if description}}
    <p>{{{description}}}</p>
    {{/if}}
{{/if}}
<p>
<a href="{{acceptLink}}">Annehmen</a>, <a href="{{declineLink}}">Decline</a>, <a href="{{tentativeLink}}">mit Vorbehalt</a>
</p>
{{#if isUser}}
<p><a href="{{recordUrl}}">Eintrag öffnen</a></p>
{{/if}}