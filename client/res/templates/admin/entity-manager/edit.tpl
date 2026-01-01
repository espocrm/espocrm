<div class="page-header">
    <h3><a href="#Admin">{{translate 'Administration'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        <a href="#Admin/entityManager">{{translate 'Entity Manager' scope='Admin'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        {{#unless isNew}}
        <a href="#Admin/entityManager/scope={{scope}}">{{translate scope category='scopeNames'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        {{translate 'Edit'}}
        {{else}}
        {{translate 'Create Entity' scope='Admin'}}
        {{/unless}}
    </h3>
</div>

<div class="record">{{{record}}}</div>
