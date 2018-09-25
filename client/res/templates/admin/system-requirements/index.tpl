<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a> &raquo {{translate 'System Requirements' scope='Admin'}}</h3></div>

<div class="panel panel-default">
    <table class="table table-striped">
        <thead>
            <tr>
                <th colspan="3">{{translate 'PHP Settings' scope='Admin'}}</th>
            </tr>
        </thead>
        <tbody>
            {{#each phpRequirementList}}
                <tr class="list-row">
                    <td class="cell col-md-5">
                        {{translate @key scope='Admin' category='systemRequirements'}}
                    </td>
                    <td class="cell col-md-3">{{actual}}</td>
                    <td class="cell col-md-4">
                        {{#if acceptable}} <span class="text-success">{{translate 'Success' scope='Admin'}}</span> {{else}} <span class="text-danger">{{translate 'Fail' scope='Admin'}}
                            {{#ifEqual type 'lib'}} ({{translate 'extension is missing' scope='Admin'}}) {{/ifEqual}}
                            {{#ifEqual type 'param'}} ({{required}} {{translate 'is recommended' scope='Admin'}}) {{/ifEqual}}
                        </span> {{/if}}
                    </td>
                </tr>
            {{/each}}
        </tbody>
    </table>
</div>

<div class="panel panel-default">
    <table class="table table-striped">
        <thead>
            <tr>
                <th colspan="3">{{translate 'Database Settings' scope='Admin'}}</th>
            </tr>
        </thead>
        <tbody>
            {{#each databaseRequirementList}}
                <tr class="list-row">
                    <td class="cell col-md-5">
                        {{translate @key scope='Admin' category='systemRequirements'}}
                    </td>
                    <td class="cell col-md-3">{{actual}}</td>
                    <td class="cell col-md-5">
                        {{#if acceptable}} <span class="text-success">{{translate 'Success' scope='Admin'}}</span> {{else}} <span class="text-danger">{{translate 'Fail' scope='Admin'}}
                            {{#ifEqual type 'param'}} ({{required}} {{translate 'is recommended' scope='Admin'}}) {{/ifEqual}}
                        </span> {{/if}}
                    </td>
                </tr>
            {{/each}}
        </tbody>
    </table>
</div>

<div class="panel panel-default">
    <table class="table table-striped">
        <thead>
            <tr>
                <th colspan="3">{{translate 'Permissions' scope='Admin'}}</th>
            </tr>
        </thead>
        <tbody>
            {{#each permissionRequirementList}}
                <tr class="list-row">
                    <td class="cell col-md-5">
                        {{translate @key scope='Admin' category='systemRequirements'}}
                    </td>
                    <td class="cell col-md-3">{{translate type scope='Admin' category='systemRequirements'}}</td>
                    <td class="cell col-md-4">
                        {{#if acceptable}} <span class="text-success">{{translate 'Success' scope='Admin'}}</span> {{else}} <span class="text-danger">{{translate 'Fail' scope='Admin'}}</span> {{/if}}
                    </td>
                </tr>
            {{/each}}
        </tbody>
    </table>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="pull-right">
            <a target="_blank" href="https://www.espocrm.com/documentation/administration/server-configuration/" style="font-weight:bold;">Configuration Instructions</a>
        </div>
    </div>
</div>
