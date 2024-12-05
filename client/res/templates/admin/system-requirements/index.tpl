<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span></span></span>
{{translate 'System Requirements' scope='Admin'}}</h3></div>

<div class="panel panel-default">
    <table class="table table-striped table-no-overflow table-fixed">
        <thead>
            <tr>
                <th><h5>{{translate 'PHP Settings' scope='Admin'}}</h5></th>
                <th style="width: 24%"></th>
                <th style="width: 24%"></th>
            </tr>
        </thead>
        <tbody>
            {{#each phpRequirementList}}
                <tr class="list-row">
                    <td class="cell">
                        {{translate @key scope='Admin' category='systemRequirements'}}
                    </td>
                    <td class="cell">{{actual}}</td>
                    <td class="cell">
                        {{#if acceptable}} <span class="text-success">{{translate 'Success' scope='Admin'}}</span>
                        {{else}}
                        <span class="text-danger">{{translate 'Fail' scope='Admin'}}
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
    <table class="table table-striped table-no-overflow table-fixed">
        <thead>
            <tr>
                <th><h5>{{translate 'Database Settings' scope='Admin'}}</h5></th>
                <th style="width: 24%"></th>
                <th style="width: 24%"></th>
            </tr>
        </thead>
        <tbody>
            {{#each databaseRequirementList}}
                <tr class="">
                    <td class="cell">
                        {{translate @key scope='Admin' category='systemRequirements'}}
                    </td>
                    <td class="cell" style="width: 24%">{{actual}}</td>
                    <td class="cell" style="width: 24%">
                        {{#if acceptable}}
                        <span class="text-success">{{translate 'Success' scope='Admin'}}</span>
                        {{else}}
                        <span class="text-danger">{{translate 'Fail' scope='Admin'}}
                            {{#ifEqual type 'param'}} ({{required}} {{translate 'is recommended' scope='Admin'}}) {{/ifEqual}}
                        </span>
                        {{/if}}
                    </td>
                </tr>
            {{/each}}
        </tbody>
    </table>
</div>

<div class="panel panel-default">
    <table class="table table-striped table-no-overflow table-fixed">
        <thead>
            <tr>
                <th><h5>{{translate 'Permissions' scope='Admin'}}</h5></th>
                <th style="width: 24%"></th>
                <th style="width: 24%"></th>
            </tr>
        </thead>
        <tbody>
            {{#each permissionRequirementList}}
                <tr>
                    <td class="cell">
                        {{translate @key scope='Admin' category='systemRequirements'}}
                    </td>
                    <td class="cell" style="width: 24%">{{translate type scope='Admin' category='systemRequirements'}}</td>
                    <td class="cell" style="width: 24%">
                        {{#if acceptable}}
                        <span class="text-success">{{translate 'Success' scope='Admin'}}</span>
                        {{else}}
                        <span class="text-danger">{{translate 'Fail' scope='Admin'}}</span>
                        {{/if}}
                    </td>
                </tr>
            {{/each}}
        </tbody>
    </table>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="pull-right">
            <a
                target="_blank"
                href="https://docs.espocrm.com/administration/server-configuration/"
            ><strong>{{translate 'Configuration Instructions' scope='Admin'}}</strong></a>
        </div>
    </div>
</div>
