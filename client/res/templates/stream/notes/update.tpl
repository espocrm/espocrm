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
        {{#if fieldsArr.length}}
        <a role="button" tabindex="0" data-action="expandDetails"><span class="fas fa-chevron-down"></span></a>
        {{/if}}
    </div>
</div>

<div class="hidden details stream-details-container">
    <ul>
        <table class="table audited-summary-table">
            <tbody>
            {{#each fieldsArr}}
                <tr>
                    <td style="width: 30%">
                        <span class="text-muted">{{translate field category='fields' scope=../parentType}}</span>
                    </td>
                    <td style="width: 30%">
                        {{#unless noValues}}
                            {{{var was ../this}}}
                        {{/unless}}
                    </td>
                    <td style="width: 10%; text-align: center;">
                        {{#unless noValues}}
                            <span class="text-muted small fas fa-arrow-right"></span>
                        {{/unless}}
                    </td>
                    <td style="width: 30%">
                        {{#unless noValues}}
                            {{{var became ../this}}}
                        {{/unless}}
                    </td>
                </tr>
            {{/each}}
            </tbody>
        </table>
    </ul>
</div>

<div class="stream-date-container">
    <a class="text-muted small" href="#Note/view/{{model.id}}">{{{createdAt}}}</a>
</div>
