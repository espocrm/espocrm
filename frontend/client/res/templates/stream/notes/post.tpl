{{#unless onlyContent}}
<li data-id="{{model.id}}" class="list-group-item">
{{/unless}}
    
    {{#unless noEdit}}
    <div class="list-row-buttons pull-right right">
        {{#if acl.edit}}
        <div class="btn-group">
        <button type="button" class="btn btn-link btn-sm dropdown-toggle" data-toggle="dropdown">
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu pull-right">
            <li><a href="javascript:" class="action" data-action="quickEdit" data-id="{{model.id}}">{{translate 'Edit'}}</a></li>
            <li><a href="javascript:" class="action" data-action="quickRemove" data-id="{{model.id}}">{{translate 'Remove'}}</a></li>
        </ul>
        </div>
        {{/if}}
    </div>
    {{/unless}}
    
    
    <div class="stream-head-container">
        {{{avatar}}}
        <span class="text-muted message">{{{message}}}</span>
    </div>
    
    {{#if post}}
    <div class="stream-post-container">
        <span class="cell cell-post">{{{post}}}</span>
    </div>
    {{/if}}
    
    {{#if attachments}}
    <div class="stream-attachments-container">
        <span class="cell cell-attachments">{{{attachments}}}</span>
    </div>
    {{/if}}
    
    <div class="stream-date-container">
        <span class="text-muted small">{{{createdAt}}}</span>
    </div>
    
{{#unless onlyContent}}
</li>
{{/unless}}
