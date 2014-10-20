<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a> &raquo {{translate 'Field Manager' scope='Admin'}}</h3></div>

<div class="row">
    <div id="scopes-menu" class="col-sm-3">
    <ul class="list-group">
    {{#each scopeList}}        
        <li class="list-group-item"><a href="javascript:" class="scope-link" data-scope="{{./this}}">{{translate this category='scopeNamesPlural'}}</a></li>        
    {{/each}}
    </ul>
    </div>

    <div id="fields-panel" class="col-sm-9">
        <h4 id="fields-header" style="margin-top: 0px;"></h4>
        <div id="fields-content">
            {{{content}}}
        </div>    
    </div>
</div>




