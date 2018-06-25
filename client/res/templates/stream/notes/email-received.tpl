

    {{#unless noEdit}}
    <div class="pull-right right-container cell-buttons">
    {{{right}}}
    </div>
    {{/unless}}

    <div class="stream-head-container">
        <div class="pull-left">
            {{{avatar}}}
        </div>
        <div class="stream-head-text-container">
            <span class="text-muted"><span class="{{emailIconClassName}} action icon" style="cursor: pointer;" title="{{translate 'View'}}" data-action="quickView" data-id="{{emailId}}" data-scope="Email"></span>
                {{{message}}}
            </span>
        </div>
    </div>

    <div class="stream-subject-container">
        <span class="cell cell-name"><a href="#Email/view/{{emailId}}">{{emailName}}</a></span>
    </div>

    {{#if hasPost}}
    <div class="stream-post-container">
        <span class="cell cell-post">{{{post}}}</span>
    </div>
    {{/if}}

    {{#if hasAttachments}}
    <div class="stream-attachments-container">
        <span class="cell cell-attachments">{{{attachments}}}</span>
    </div>
    {{/if}}

    <div class="stream-date-container">
        <span class="text-muted small">{{{createdAt}}}</span>
    </div>

