

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
            <span class="text-muted message">{{{message}}}</span>
            <a href="javascript:" data-action="expandDetails"><span class="fas fa-chevron-down"></span></a>
        </div>
    </div>

    <div class="hidden details stream-details-container">
        <ul>
            {{#each fieldsArr}}
            <li>
                <span>{{translate field category='fields' scope=../parentType}}</span>
                {{#unless noValues}}
                &nbsp;<span class="text-muted">:</span>&nbsp;
                <span class="inline-block-child-div">{{{var was ../this}}}</span>
                &nbsp;<span class="text-muted small fas fa-arrow-right"></span>&nbsp;
                <span class="inline-block-child-div">{{{var became ../this}}}</span>
                {{/unless}}
            </li>
            {{/each}}
        </ul>
    </div>

    <div class="stream-date-container">
        <span class="text-muted small">{{{createdAt}}}</span>
    </div>
