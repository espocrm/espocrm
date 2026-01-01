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
        <span
            class="{{emailIconClassName}} text-muted action icon"
            style="cursor: pointer;"
            title="{{translate 'View'}}"
            data-action="quickView"
            data-id="{{emailId}}"
            data-scope="Email"
        ></span><span class="message text-muted">{{{message}}}</span>
        {{#if hasExpand}}
            <a
                role="button"
                tabindex="0"
                data-action="expandDetails"
            ><span class="fas {{#if detailsIsShown}} fa-chevron-up {{else}} fa-chevron-down {{/if}} "></span></a>
        {{/if}}
    </div>
</div>

<div class="stream-subject-container">
    <span class="cell cell-name"><a
        href="#Email/view/{{emailId}}"
        data-id="{{emailId}}"
        data-scope="Email"
    >{{emailName}}</a></span>
</div>

{{#if detailsIsShown}}
    {{#if bodyField}}
        <div class="details stream-details-container">
            <div class="cell" data-name="body">{{{bodyField}}}</div>
            {{#if attachmentsField}}
                <div data-name="attachments" class="cell margin-top">{{{attachmentsField}}}</div>
            {{/if}}
        </div>
    {{/if}}
{{/if}}

{{#if hasPost}}
<div class="stream-post-container">
    <span class="cell cell-post {{#if mutedPost}} text-muted {{/if}}">{{{post}}}</span>
</div>
{{/if}}

{{#if hasAttachments}}
<div class="stream-attachments-container">
    <span class="cell cell-attachments">{{{attachments}}}</span>
</div>
{{/if}}

<div class="stream-date-container">
    <a class="text-muted small" href="#Note/view/{{model.id}}">{{{createdAt}}}</a>
    {{#if isPinned}}
        <span class="fas fa-map-pin fa-sm pin-icon" title="{{translate 'Pinned' scope='Note'}}"></span>
    {{/if}}
</div>
