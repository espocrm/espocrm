{{#if idValue}}{{#if iconHtml}}{{{iconHtml}}}{{/if}}<a href="#{{foreignScope}}/view/{{idValue}}" title="{{translate foreignScope category='scopeNames'}}">{{nameValue}}</a>
{{else}}
    {{#if valueIsSet}}
        {{#if displayEntityType}}{{translate typeValue category='scopeNames'}}
        {{else}}<span class="none-value">{{translate 'None'}}</span>
        {{/if}}
    {{else}}<span class="loading-value"></span>{{/if}}
{{/if}}
