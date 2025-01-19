<p>Objet: {{name}}</p>
<p>Débute à: {{#if isAllDay}}{{dateStartFull}}{{else}}{{dateStartFull}} ({{timeZone}}){{/if}}</p>
{{#if isUser}}
    {{#if description}}
    <p>{{{description}}}</p>
    {{/if}}
{{/if}}
<p>
    <a href="{{acceptLink}}" style="font-size: 1.2em">Accepter</a> &middot;
    <a href="{{tentativeLink}}" style="font-size: 1.2em">Tentative</a> &middot;
    <a href="{{declineLink}}" style="font-size: 1.2em">Décliner</a>
</p>
{{#if joinUrl}}
    <p>
        <a href="{{joinUrl}}">Rejoindre</a>
    </p>
{{/if}}
{{#if isUser}}
<p><a href="{{recordUrl}}">Voir la fiche</a></p>
{{/if}}
