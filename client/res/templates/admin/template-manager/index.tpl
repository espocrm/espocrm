<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span></span></span>
{{translate 'Template Manager' scope='Admin'}}</h3></div>

<div class="row">
    <div class="col-sm-3">
        <div class="panel panel-default">
            <div class="panel-body">
                <ul class="list-unstyled" style="overflow-x: hidden;">
                {{#each templateDataList}}
                    <li>
                        <button class="btn btn-link" data-name="{{name}}" data-action="selectTemplate">{{{text}}}</button>
                    </li>
                {{/each}}
                </ul>
            </div>
        </div>
    </div>

    <div class="col-sm-9">
        <div class="template-record">{{{record}}}</div>
    </div>
</div>
