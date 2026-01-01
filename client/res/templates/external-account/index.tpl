<div class="page-header"><h3>{{translate 'ExternalAccount' category='scopeNamesPlural'}}</h3></div>

<div class="row">
    <div id="external-account-menu" class="col-sm-3">
    <ul class="list-group list-group-panel{{#unless externalAccountListCount}} hidden{{/unless}}">
    {{#each externalAccountList}}
        <li class="list-group-item"><a
            role="button"
            tabindex="0"
            class="external-account-link"
            data-id="{{id}}"
        >{{id}}</a></li>
    {{/each}}
    </ul>
    {{#unless externalAccountListCount}}
        {{translate 'No Data'}}
    {{/unless}}
    </div>

    <div id="external-account-panel" class="col-sm-9">
        <h4 id="external-account-header" style="margin-top: 0px;"></h4>
        <div id="external-account-content">
            {{{content}}}
        </div>
    </div>
</div>
