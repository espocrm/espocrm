<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span class="chevron-right"></span></span>
{{translate 'Integrations' scope='Admin'}}</h3></div>

<div class="row">
    <div id="integrations-menu" class="col-sm-3">
        <ul class="list-group list-group-panel">
        {{#each integrationList}}
            <li class="list-group-item"><a href="javascript:" class="integration-link" data-name="{{./this}}">{{{translate ./this scope='Integration' category='titles'}}}</a></li>
        {{/each}}
        </ul>
    </div>

    <div id="integration-panel" class="col-sm-9">
        <h4 id="integration-header" style="margin-top: 0px;"></h4>
        <div id="integration-content">
            {{{content}}}
        </div>
    </div>
</div>
