<div class="page-header">
    <h3>
        <a href="#Admin">{{translate 'Administration'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        <a href="#Admin/entityManager">{{translate 'Entity Manager' scope='Admin'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        <a href="#Admin/entityManager/scope={{scope}}">{{translate scope category='scopeNames'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        {{translate 'Formula' scope='EntityManager'}}
    </h3>
</div>

<div class="button-container">
    <div class="btn-group actions-btn-group" role="group">
        <button class="btn btn-danger btn-xs-wide action"  data-action="save">
            {{translate 'Save'}}
        </button>
        <button class="btn btn-default btn-xs-wide action" data-action="close">
            {{translate 'Close'}}
        </button>
        <button
            class="btn btn-default dropdown-toggle"
            data-toggle="dropdown"
        ><span class="fas fa-ellipsis-h"></span></button>
        <ul class="dropdown-menu pull-right">
            <li>
                <a
                    role="button"
                    tabindex="0"
                    data-action="resetToDefault"
                >{{translate 'Reset to Default' scope='Admin'}}</a>
            </li>
        </ul>
    </div>
</div>

<div class="record">{{{record}}}</div>
