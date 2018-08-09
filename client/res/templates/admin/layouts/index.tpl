<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a> &raquo {{translate 'Layout Manager' scope='Admin'}}</h3></div>

<div class="row">
    <div id="layouts-menu" class="col-sm-3">
        <div class="panel-group" id="layout-accordion">
        {{#each layoutScopeDataList}}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <a class="accordion-toggle" data-parent="#layout-accordion" data-toggle="collapse" href="#collapse-{{toDom scope}}">{{translate scope category='scopeNamesPlural'}}</a>
                </div>
                <div id="collapse-{{toDom scope}}" class="panel-collapse collapse{{#ifEqual scope ../scope}} in{{/ifEqual}}">
                    <div class="panel-body">
                        <ul class="list-unstyled" style="overflow-x: hidden;";>
                        {{#each typeList}}
                            <li>
                                <button style="display: block;" class="layout-link btn btn-link" data-type="{{./this}}" data-scope="{{../scope}}">{{translate this scope='Admin' category='layouts'}}</button>
                            </li>
                        {{/each}}
                        </ul>
                    </div>
                </div>
            </div>
        {{/each}}
        </div>
    </div>

    <div id="layouts-panel" class="col-sm-9">
        <h4 id="layout-header" style="margin-top: 0px;"></h4>
        <div id="layout-content">
            {{{content}}}
        </div>
    </div>
</div>




