<div class="page-header">
    <h3><a href="#Admin">{{translate 'Administration'}}</a>
        <span class="breadcrumb-separator"><span class="chevron-right"></span></span>
        <a href="#Admin/entityManager">{{translate 'Entity Manager' scope='Admin'}}</a>
        <span class="breadcrumb-separator"><span class="chevron-right"></span></span>
        {{scope}}
    </h3>
</div>

<div class="button-container">
    <div class="btn-group actions-btn-group" role="group">
        <button class="btn btn-default action btn-lg action btn-wide" data-action="editEntity">
            <span class="icon fas fa-cog"></span>
            {{translate 'Edit'}}
        </button>
        {{#if isRemovable}}
        <button class="btn btn-default btn-lg dropdown-toggle item-dropdown-button" data-toggle="dropdown">
            <span class="fas fa-ellipsis-h"></span>
        </button>
        <ul class="dropdown-menu pull-left">
            <li><a href="javascript:" data-action="removeEntity">{{translate 'Remove'}}</a></li>
        </ul>
        {{/if}}
    </div>
</div>

<div class="record">
    <div class="record-grid">
        <div class="left">
            <div class="panel panel-default">
                <div class="panel-body panel-body-form">
                    <div class="row">
                        <div class="cell col-sm-6 form-group">
                            <label class="control-label">{{translate 'name' scope='EntityManager' category='fields'}}</label>
                            <div class="field">
                                {{scope}}
                            </div>
                        </div>
                        {{#if type}}
                        <div class="cell col-sm-6 form-group">
                            <label class="control-label">{{translate 'type' scope='EntityManager' category='fields'}}</label>
                            <div class="field">
                                {{type}}
                            </div>
                        </div>
                        {{/if}}
                    </div>
                    <div class="row">
                        <div class="cell col-sm-6 form-group">
                            <label class="control-label">{{translate 'label' scope='EntityManager' category='fields'}}</label>
                            <div class="field">
                                {{label}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-body panel-body-form">
                    <div class="row">
                        <div class="cell col-sm-4 form-group">
                            {{#if isCustomizable}}
                            <div class="">
                                <a class="btn btn-default btn-lg action btn-full-wide"
                                href="#Admin/fieldManager/scope={{scope}}">
                                    <span class="fas fa-asterisk"></span>
                                    {{translate 'Fields' scope='EntityManager'}}
                                </a>
                            </div>
                            {{/if}}
                        </div>
                        <div class="cell col-sm-4 form-group">
                            {{#if isCustomizable}}
                            <div class="">
                                <a class="btn btn-default btn-lg action btn-full-wide"
                                href="#Admin/linkManager/scope={{scope}}">
                                    <span class="fas fa-link"></span>
                                    {{translate 'Relationships' scope='EntityManager'}}
                                </a>
                            </div>
                            {{/if}}
                        </div>
                        <div class="cell col-sm-4 form-group">
                            {{#if hasLayouts}}
                            <div class="">
                                <a class="btn btn-default btn-lg action btn-full-wide"
                                href="#Admin/layouts/scope={{scope}}&em=true">
                                    <span class="fas fa-table"></span>
                                    {{translate 'Layouts' scope='EntityManager'}}
                                </a>
                            </div>
                            {{/if}}
                        </div>
                    </div>
                    <div class="row">
                        <div class="cell col-sm-4 form-group">
                            {{#if isCustomizable}}
                            <div class="">
                                <a class="btn btn-default btn-lg action btn-full-wide"
                                href="javascript:" data-action="editFormula">
                                    <span class="fas fa-code"></span>
                                    {{translate 'Formula' scope='EntityManager'}}
                                </a>
                            </div>
                            {{/if}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

