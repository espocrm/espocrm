

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
            <span class="text-muted message">{{{message}}}</span> <a href="javascript:" data-action="expandDetails"><span class="fas fa-chevron-down"></span></a>
        </div>
    </div>

    <div class="hidden details stream-details-container">
        <ul>
            {{#each fieldsArr}}
            <li>
                <span>{{translate field category='fields' scope=../parentType}}</span> <span class="text-soft">&raquo;</span> </span> <span class="inline-block-child-div">{{{var was ../this}}}</span> <span class="text-soft">&rarr;</span> <span class="inline-block-child-div">{{{var became ../this}}}</span>
            </li>
            {{/each}}
        </ul>
    </div>

    <div class="stream-date-container">
        <span class="text-muted small">{{{createdAt}}}</span>
    </div>
