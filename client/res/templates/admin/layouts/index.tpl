<div class="page-header"><h3>{{{headerHtml}}}</h3></div>

<div class="row">
    <div id="layouts-menu" class="col-sm-3">
        <div class="panel-group panel-group-accordion" id="layout-accordion">
        {{#each layoutScopeDataList}}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <a
                        class="accordion-toggle{{#if ../em}} btn btn-link{{/if}}"
                        data-scope="{{scope}}" href="{{url}}"
                    >{{translate scope category='scopeNamesPlural'}}</a>
                </div>
                <div class="panel-collapse collapse{{#ifEqual scope ../scope}} in{{/ifEqual}}" data-scope="{{scope}}">
                    <div class="panel-body">
                        <ul class="list-unstyled" style="overflow-x: hidden;">
                        {{#each typeDataList}}
                            <li>
                                <a
                                    class="layout-link btn btn-link"
                                    data-type="{{type}}"
                                    data-scope="{{../scope}}"
                                    href="{{url}}"
                                >{{label}}</a>
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
        <div id="layout-content" class="">
            {{{content}}}
        </div>
    </div>
</div>
