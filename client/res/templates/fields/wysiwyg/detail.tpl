{{#unless isPlain}}
    {{#if useIframe}}
    <div class="wysiwyg-iframe-container">
        <iframe frameborder="0" style="width: 100%; overflow-x: hidden; overflow-y: hidden;" class="hidden wysiwyg"></iframe>
    </div>
    {{else}}
    <div class="html-container">{{{value}}}</div>
    {{/if}}
{{else}}
<div class="plain complex-text hidden">{{complexText value}}</div>
{{/unless}}
{{#if isNone}}<span class="none-value">{{translate 'None'}}</span>{{/if}}
