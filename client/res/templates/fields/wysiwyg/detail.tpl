{{#unless isPlain}}
    {{#if useIframe}}
    <iframe frameborder="0"  style="width: 100%; overflow-y: hidden; overflow-x: scroll;" class="hidden"></iframe>
    {{else}}
    <div class="html-container">{{{value}}}</div>
    {{/if}}
{{else}}
<div class="plain hidden">{{complexText value}}</div>
{{/unless}}
