<p>Betreff: {{name}}</p>
<p>Beginn: {{#if isAllDay}}{{dateStartFull}}{{else}}{{dateStartFull}} ({{timeZone}}){{/if}}</p>
{{#if isUser}}
    {{#if description}}
    <p>{{{description}}}</p>
    {{/if}}
{{/if}}
<p>
    <a href="{{acceptLink}}" style="font-size: 1.2em">Annehmen</a> &middot;
    <a href="{{tentativeLink}}" style="font-size: 1.2em">mit Vorbehalt</a> &middot;
    <a href="{{declineLink}}" style="font-size: 1.2em">Ablehnen</a>
</p>
{{#if joinUrl}}
    <p>
        <a href="{{joinUrl}}">Beitreten</a>
    </p>
{{/if}}
{{#if isUser}}
<p><a href="{{recordUrl}}">Eintrag öffnen</a></p>
{{/if}}
