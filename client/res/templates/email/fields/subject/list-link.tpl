{{#if isReplied}}
<span class="glyphicon glyphicon-share-alt small text-muted icon-flip-horizontal pull-right"></span>
{{/if}}
{{#if hasAttachment}}
<span class="glyphicon glyphicon-paperclip small text-muted pull-right"></span>
{{/if}}
{{#unless isRead}}<strong>{{/unless}}
<a href="#{{model.name}}/view/{{model.id}}" class="link{{#if isImportant}} text-warning{{/if}}" data-id="{{model.id}}" title="{{value}}">{{value}}</a>
{{#unless isRead}}</strong>{{/unless}}

