<p>Oggetto: {{name}}</p>
<p>Inizio: {{#if isAllDay}}{{dateStartFull}}{{else}}{{dateStartFull}} ({{timeZone}}){{/if}}</p>
{{#if isUser}}
    {{#if description}}
    <p>{{{description}}}</p>
    {{/if}}
{{/if}}
<p>
    <a href="{{acceptLink}}" style="font-size: 1.2em">Accetta</a> &middot;
    <a href="{{tentativeLink}}" style="font-size: 1.2em">Forse</a> &middot;
    <a href="{{declineLink}}" style="font-size: 1.2em">Rifiuta</a>
</p>
{{#if joinUrl}}
    <p>
        <a href="{{joinUrl}}">Partecipa</a>
    </p>
{{/if}}
{{#if isUser}}
<p><a href="{{recordUrl}}">Vedi record</a></p>
{{/if}}
