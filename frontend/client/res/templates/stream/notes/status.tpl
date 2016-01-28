

    {{#unless noEdit}}
    <div class="pull-right right-container">
    {{{right}}}
    </div>
    {{/unless}}

    <div class="stream-head-container">
        <div class="pull-left">
            {{{avatar}}}
        </div>
        <div class="stream-head-text-container">
            <span class="label label-{{style}}">{{statusText}}</span>
            <span class="text-muted message">{{{message}}}</span>
        </div>
    </div>

    <div class="stream-date-container">
        <span class="text-muted small">{{{createdAt}}}</span>
    </div>
