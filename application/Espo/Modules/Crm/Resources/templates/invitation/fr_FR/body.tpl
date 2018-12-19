<p>Objet: {{name}}</p>
<p>Débute à: {{#if isAllDay}}{{dateStartDate}}{{else}}{{dateStart}}{{/if}}</p>
{{#if isUser}}
    {{#if description}}
    <p>{{{description}}}</p>
    {{/if}}
{{/if}}
<p>
<a href="{{acceptLink}}">Accepter</a>, <a href="{{declineLink}}">Décliner</a>, <a href="{{tentativeLink}}">Tentative</a>
</p>
{{#if isUser}}
<p><a href="{{recordUrl}}">Voir la fiche</a></p>
{{/if}}