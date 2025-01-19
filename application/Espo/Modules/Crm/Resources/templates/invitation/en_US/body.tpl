<p>Subject: {{name}}</p>
<p>Start: {{#if isAllDay}}{{dateStartFull}}{{else}}{{dateStartFull}} ({{timeZone}}){{/if}}</p>
{{#if isUser}}
    {{#if description}}
    <p>{{{description}}}</p>
    {{/if}}
{{/if}}
<p>
    <a href="{{acceptLink}}" style="font-size: 1.2em">Accept</a> &middot;
    <a href="{{tentativeLink}}" style="font-size: 1.2em">Tentative</a> &middot;
    <a href="{{declineLink}}" style="font-size: 1.2em">Decline</a>
</p>
{{#if joinUrl}}
    <p>
        <a href="{{joinUrl}}">Join</a>
    </p>
{{/if}}
{{#if isUser}}
<p><a href="{{recordUrl}}">View record</a></p>
{{/if}}
