<div class="page-header">
    <h3>
        <a href="#Admin">{{translate 'Administration'}}</a>
        <span class="breadcrumb-separator"><span class="chevron-right"></span></span>
        <a href="#Admin/entityManager">{{translate 'Entity Manager' scope='Admin'}}</a>
        <span class="breadcrumb-separator"><span class="chevron-right"></span></span>
        {{translate scope category='scopeNames'}}
        <span class="breadcrumb-separator"><span class="chevron-right"></span></span>
        {{translate 'Fields' scope='EntityManager'}}
    </h3>
</div>

<div class="row">
    <div id="fields-panel" class="col-sm-9">
        <div id="fields-content">
            {{{content}}}
        </div>
    </div>
</div>
