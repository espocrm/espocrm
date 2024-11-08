<div class="stream-head-container">
    <div class="pull-left">
        {{{avatar}}}
    </div>
    <div class="stream-head-text-container">
        <span
            class="fas fa-envelope text-muted action icon"
            style="cursor: pointer;"
            title="{{translate 'View'}}"
            data-action="quickView"
            data-id="{{emailId}}"
            data-scope="Email"
        ></span><span class="message text-muted">{{{message}}}</span>
    </div>
</div>

<div class="stream-subject-container">
    <span class="cell cell-name"><a
        href="#Email/view/{{emailId}}"
        data-id="{{emailId}}"
        data-scope="Email"
    >{{emailName}}</a></span>
</div>

<div class="stream-date-container">
    <span class="text-muted small">{{{createdAt}}}</span>
</div>
