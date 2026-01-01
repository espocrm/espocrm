<h3>
    <a href="#Admin">{{translate 'Administration'}}</a>
    <span class="breadcrumb-separator"><span></span></span>
    <a href="#Admin/entityManager">{{translate 'Entity Manager' scope='Admin'}}</a>
    <span class="breadcrumb-separator"><span></span></span>
    <a href="#Admin/entityManager/scope={{scope}}">{{translate scope category='scopeNames'}}</a>
    <span class="breadcrumb-separator"><span></span></span>
    {{#if field}}
    <a href="#Admin/fieldManager/scope={{scope}}">{{translate 'Fields' scope='EntityManager'}}</a>
    {{else}}
    {{translate 'Fields' scope='EntityManager'}}
    {{/if}}
    {{#if field}}
    <span class="breadcrumb-separator"><span></span></span>
    {{translate field category='fields' scope=scope}}
    {{/if}}
</h3>
