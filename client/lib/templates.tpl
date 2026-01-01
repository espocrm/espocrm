_delimiter_6nomlrqbt5x
res/templates/stream.tpl
<div class="page-header">
    <div class="row">
        <div class="col-sm-7 col-xs-5">
            {{#if displayTitle}}
            <h3><span
                data-action="fullRefresh"
                style="user-select: none; cursor: pointer"
            >{{translate 'Stream'}}</span></h3>
            {{/if}}
        </div>
        <div class="col-sm-5 col-xs-7">
            <div class="pull-right btn-group">
                <button
                    class="btn btn-default btn-xs-wide"
                    data-action="createPost"
                ><span class="fas fa-plus fa-sm"></span><span>{{translate 'Create Post'}}</span></button>
                {{#if hasMenu}}
                    <button
                        class="btn btn-default dropdown-toggle"
                        data-toggle="dropdown"
                    ><span class="fas fa-ellipsis-h"></span></button>
                    <ul class="dropdown-menu pull-right">
                        {{#if hasGlobalStreamAccess}}
                        <li>
                            <a
                                role="button"
                                tabindex="0"
                                href="#GlobalStream"
                            >{{translate 'GlobalStream' category='scopeNames'}}</a>
                        </li>
                        {{/if}}
                    </ul>
                {{/if}}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="button-container clearfix" tabindex="-1">
            <div class="btn-group">
                {{#each filterList}}
                    <button
                        class="btn btn-text btn-xs-wide {{#ifEqual this ../filter}} active{{/ifEqual}}"
                        data-action="selectFilter"
                        data-name="{{./this}}"
                    >{{translate this scope='Note' category='filters'}}</button>
                {{/each}}
            </div>
            <button
                class="btn btn-text btn-icon pull-right"
                data-action="refresh"
                title="{{translate 'checkForNewNotes' category='messages'}}"
            ><span class="fas fa-sync-alt fa-sm icon"></span></button>
        </div>
        <div class="list-container list-container-panel">{{{list}}}</div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/merge.tpl
<div class="page-header">{{{header}}}</div>
<div class="body">{{{body}}}</div>

_delimiter_6nomlrqbt5x
res/templates/logout-wait.tpl
<div></div>

_delimiter_6nomlrqbt5x
res/templates/login.tpl
<div class="container content">
    <div class="container-centering">
    <div id="login" class="panel panel-default block-center-sm">
        <div class="panel-heading">
            <div class="logo-container">
                <img src="{{logoSrc}}" class="logo">
            </div>
        </div>
        <div class="panel-body{{#if anotherUser}} another-user{{/if}}">
            <div class="">
                <form id="login-form">
                    {{#if hasSignIn}}
                    <div class="cell" data-name="sign-in">
                        {{#if hasFallback}}
                        <div class="pull-right">
                            <a
                                role="button"
                                tabindex="0"
                                class="btn btn-link btn-icon"
                                data-action="showFallback"
                            ><span class="fas fa-chevron-down"></span></a>
                        </div>
                        {{/if}}
                        <button
                            class="btn btn-default btn-x-wide"
                            id="sign-in"
                            type="button"
                        >{{signInText}}</button>
                    </div>
                    {{/if}}
                    <div class="form-group cell" data-name="username">
                        <label for="field-userName">{{translate 'Username'}}</label>
                        <input
                            type="text"
                            name="username"
                            id="field-userName"
                            class="form-control"
                            autocapitalize="off"
                            spellcheck="false"
                            tabindex="1"
                            autocomplete="username"
                            maxlength="255"
                        >
                    </div>
                    <div class="form-group cell" data-name="password">
                        <label for="field-password">{{translate 'Password'}}</label>
                        <div data-role="password-input-container">
                            <input
                                type="password"
                                name="password"
                                id="field-password"
                                class="form-control"
                                tabindex="2"
                                autocomplete="current-password"
                                maxlength="255"
                            >
                            <a
                                role="button"
                                data-action="toggleShowPassword"
                                class="text-soft"
                                title="{{translate 'View'}}"
                            ><span class="far fa-eye"></span></a>
                        </div>
                    </div>
                    {{#if anotherUser}}
                    <div class="form-group cell">
                        <label>{{translate 'Log in as'}}</label>
                        <div>{{anotherUser}}</div>
                    </div>
                    {{/if}}
                    <div class="margin-top-2x cell" data-name="submit">
                        {{#if showForgotPassword}}
                        <a
                            role="button"
                            class="btn btn-link btn-text btn-text-hoverable btn-sm pull-right margin-top-sm"
                            data-action="passwordChangeRequest"
                            tabindex="4"
                        >{{translate 'Forgot Password?' scope='User'}}</a>{{/if}}
                        <button
                            type="submit"
                            class="btn btn-primary btn-s-wide"
                            id="btn-login"
                            tabindex="3"
                        >{{logInText}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>
<footer>{{{footer}}}</footer>

_delimiter_6nomlrqbt5x
res/templates/login-second-step.tpl
<div class="container content">
    <div class="container-centering">
    <div id="login" class="panel panel-default block-center-sm">
        <div class="panel-body">
            <div>
                <p>{{message}}</p>
                <form id="login-form">
                    <div class="form-group cell">
                        <label for="field-code">{{translate 'Code' scope='User'}}</label>
                        <input
                            type="text"
                            data-name="field-code"
                            id="field-code"
                            class="form-control"
                            autocapitalize="off"
                            spellcheck="false"
                            tabindex="1"
                            autocomplete="one-time-code"
                            maxlength="7"
                        >
                    </div>
                    <div class="margin-top-2x">
                        <a
                            role="button"
                            class="btn btn-link pull-right"
                            data-action="backToLogin"
                            tabindex="4"
                        >{{translate 'Back to login form' scope='User'}}</a>
                        <button
                            type="submit"
                            class="btn btn-primary btn-s-wide"
                            id="btn-send"
                            tabindex="2"
                        >{{translate 'Submit'}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>
<footer>{{{footer}}}</footer>

_delimiter_6nomlrqbt5x
res/templates/list.tpl
<div class="page-header">{{{header}}}</div>
<div class="search-container">{{{search}}}</div>
<div class="list-container">{{{list}}}</div>

_delimiter_6nomlrqbt5x
res/templates/list-with-categories.tpl
<div class="page-header">{{{header}}}</div>
<div class="search-container">{{{search}}}</div>

{{#unless fallback}}
<div class="row">
    {{#if hasTree}}
    <div class="{{#if hasTree}} col-md-3 col-sm-4{{else}} col-md-12{{/if}} list-categories-column">
        <div class="categories-container">{{{categories}}}</div>
    </div>
    {{/if}}
    <div class="{{#if hasTree}} col-md-9 col-sm-8{{else}} col-md-12{{/if}} list-main-column">
        <div class="nested-categories-container{{#unless hasNestedCategories}} hidden{{/unless}}">{{{nestedCategories}}}</div>
        <div class="list-container">{{{list}}}</div>
    </div>
</div>
{{else}}
<div class="list-container">{{{list}}}</div>
{{/unless}}

_delimiter_6nomlrqbt5x
res/templates/home.tpl
<div class="home-content">{{{content}}}</div>

_delimiter_6nomlrqbt5x
res/templates/header.tpl
<div class="page-header-row">
    <div class="{{#if noBreakWords}} no-break-words{{/if}} page-header-column-1">
        <h3 class="header-title">{{{header}}}</h3>
    </div>
    <div class="page-header-column-2">
        <div class="header-buttons btn-group pull-right{{#if menuItemsHidden}} hidden{{/if}}">
            {{#each items.buttons}}
                <a
                    {{#if link}}href="{{link}}"{{else}}role="button"{{/if}}
                    tabindex="0"
                    class="btn btn-{{#if style}}{{style}}{{else}}default{{/if}} btn-xs-wide main-header-manu-action action{{#if disabled}} disabled{{/if}}{{#if hidden}} hidden{{/if}}{{#if className}} {{className}}{{/if}}"
                    data-name="{{name}}"
                    data-action="{{action}}"
                    {{#each data}} data-{{hyphen @key}}="{{./this}}"{{/each}}
                    {{#if title}}title="{{title}}"{{/if}}
                >
                {{#if iconHtml~}}
                    {{{iconHtml}}}
                {{~else~}}
                    {{#if iconClass}}<span class="{{iconClass}}"></span>{{/if~}}
                {{~/if~}}
                    <span>{{#if html}}{{{html}}}{{else}}{{#if text}}{{text}}{{else}}{{translate label scope=../scope}}{{/if}}{{/if}}</span>
                </a>
            {{/each}}

            {{#if items.actions}}
                <div class="btn-group" role="group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    {{translate 'Actions'}} <span class="caret"></span>
                </button>
                <ul class="dropdown-menu pull-right">
                    {{#each items.actions}}
                    <li class="{{#if hidden}}hidden{{/if}}">
                        <a
                            {{#if link}}href="{{link}}"{{else}}role="button"{{/if}}
                            tabindex="0"
                            class="action main-header-manu-action{{#if disabled}} disabled{{/if}}"
                            data-name="{{name}}"
                            data-action="{{action}}"
                            {{#each data}} data-{{@key}}="{{./this}}"{{/each}}
                            {{#if title}}title="{{title}}"{{/if}}
                        >{{#if iconHtml}}{{{iconHtml}}}{{/if}}
                            {{#if html}}{{{html}}}{{else}}{{#if text}}{{text}}{{else}}{{translate label scope=../scope}}{{/if}}{{/if}}</a></li>
                    {{/each}}
                </ul>
                </div>
            {{/if}}

            {{#if items.dropdown}}
                <div class="btn-group dropdown-group{{#unless hasVisibleDropdownItems}} hidden{{/unless}}" role="group">
                <button
                    type="button"
                    class="btn btn-default dropdown-toggle{{#unless hasVisibleDropdownItems}} hidden{{/unless}}"
                    data-toggle="dropdown"
                >
                    <span class="fas fa-ellipsis-h"></span>
                </button>
                <ul class="dropdown-menu pull-right">
                    {{#each items.dropdown}}
                        {{#if this}}
                        <li class="{{#if hidden}}hidden{{/if}}">
                            <a
                                {{#if link}}href="{{link}}"{{else}}role="button"{{/if}}
                                tabindex="0"
                                class="action main-header-manu-action{{#if disabled}} disabled{{/if}}"
                                data-name="{{name}}"
                                data-action="{{action}}"
                                {{#each data}} data-{{@key}}="{{./this}}"{{/each}}
                            >
                            {{#if iconHtml}}
                                {{{iconHtml}}}
                            {{else}}
                                {{#if iconClass}}
                                    <span class="{{iconClass}}"></span>
                                {{/if}}
                            {{/if}}
                            {{#if html}}{{{html}}}{{else}}{{#if text}}{{text}}{{else}}{{translate label scope=../scope}}{{/if}}{{/if}}</a></li>
                        {{else}}
                            {{#unless @first}}
                            {{#unless @last}}
                            <li class="divider"></li>
                            {{/unless}}
                            {{/unless}}
                        {{/if}}
                    {{/each}}
                </ul>
                </div>
            {{/if}}
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/edit.tpl
<div class="header page-header">{{{header}}}</div>
<div class="record">{{{record}}}</div>

_delimiter_6nomlrqbt5x
res/templates/detail.tpl
<div class="header page-header">{{{header}}}</div>
{{#if modes}}
    <div class="modes">{{{modes}}}</div>
{{/if}}
<div class="record">{{{record}}}</div>
<div class="bottom">{{{bottom}}}</div>

_delimiter_6nomlrqbt5x
res/templates/dashlet.tpl
<div
  id="dashlet-{{id}}"
  class="panel panel-default headered dashlet{{#if isDoubleHeight}} double-height{{/if}}"
  data-name="{{name}}"
  data-id="{{id}}"
>
    <div class="panel-heading">
        <div class="btn-group pull-right">
            {{#each buttonList}}
            <button
              type="button"
              class="btn btn-{{#if ../style}}{{../style}}{{else}}default{{/if}} dashlet-action btn-sm action{{#if hidden}} hidden{{/if}}"
              data-action="{{name}}"
              data-name="{{name}}"
              title="{{#if title}}{{translate title}}{{/if}}"
            >{{#if html}}{{{html}}}{{else}}{{#if text}}{{text}}{{else}}{{translate label}}{{/if}}{{/if}}</button>
            {{/each}}
            <button
              class="dropdown-toggle btn btn-{{#if ../style}}{{../style}}{{else}}default{{/if}} btn-sm menu-button"
              data-toggle="dropdown"
            ><span class="fas fa-ellipsis-h"></span></button>
            <ul class="dropdown-menu dropdown-menu-with-icons" role="menu">
            {{#each actionList}}
                {{#if this}}
                    <li>
                        <a
                          data-action="{{name}}"
                          data-name="{{name}}"
                          class="action dashlet-action"
                          {{#if url}}href="{{url}}"{{else}}role="button"{{/if}}
                          tabindex="0"
                          {{#each data}} data-{{hyphen @key}}="{{./this}}"{{/each}}
                        >
                            {{#if iconHtml}}{{{iconHtml}}}
                            {{else}}
                            <span class="empty-icon">&nbsp;</span>
                            {{/if}}
                            <span class="item-text">{{#if html}}{{{html}}}{{else}}{{#if text}}{{text}}{{else}}{{translate label}}{{/if}}{{/if}}</span>
                        </a>
                    </li>
                {{else}}
                    <li class="divider"></li>
                {{/if}}
            {{/each}}
            </ul>
        </div>
        <h4 class="panel-title">
            <span
                data-action="refresh"
                class="action"
                title="{{translate 'Refresh'}}"
            >
                {{~#if color}}<span class="color-icon fas fa-square" style="color: {{color}}"></span><span>&nbsp;</span>{{/if~}}
                {{~#if title}}{{title}}{{else}}&nbsp;{{/if~}}
            </span>
        </h4>
    </div>
    <div class="dashlet-body panel-body{{#if noPadding}} no-padding{{/if}}">{{{body}}}</div>
</div>

_delimiter_6nomlrqbt5x
res/templates/dashboard.tpl
<div class="page-header dashboard-header">
    <div class="row">
        <div class="col-sm-4">
            {{#if displayTitle}}
            <h3>{{translate 'Dashboard' category='scopeNames'}}</h3>
            {{/if}}
        </div>
        <div class="col-sm-8 clearfix">
            {{#unless layoutReadOnly}}
            <div class="btn-group pull-right dashboard-buttons">
                <button
                    class="btn btn-text btn-icon dropdown-toggle"
                    data-toggle="dropdown"
                ><span class="fas fa-ellipsis-h"></span></button>
                <ul class="dropdown-menu pull-right dropdown-menu-with-icons">
                    <li>
                        <a role="button" tabindex="0" data-action="editTabs">
                            <span class="fas fa-pencil-alt fa-sm"></span>
                            <span class="item-text">{{translate 'Edit Dashboard'}}</span>
                        </a>
                    </li>
                    {{#if hasAdd}}
                    <li>
                        <a role="button" tabindex="0" data-action="addDashlet">
                            <span class="fas fa-plus"></span>
                            <span class="item-text">{{translate 'Add Dashlet'}}</span>
                        </a>
                    </li>
                    {{/if}}
                </ul>
            </div>
            {{/unless}}
            {{#ifNotEqual dashboardLayout.length 1}}
            <div class="btn-group pull-right dashboard-tabs">
                {{#each dashboardLayout}}
                    <button
                        class="btn btn-text{{#ifEqual @index ../currentTab}} active{{/ifEqual}}"
                        data-action="selectTab"
                        data-tab="{{@index}}"
                    >{{name}}</button>
                {{/each}}
            </div>
            {{/ifNotEqual}}
        </div>
    </div>
</div>
<div class="dashlets grid-stack grid-stack-12">{{{dashlets}}}</div>

_delimiter_6nomlrqbt5x
res/templates/clear-cache.tpl
<div class="row">
    <div class="col-md-6 col-sm-offset-2">
        <div class="panel">
            <div class="panel-body">
                {{#if cacheIsEnabled}}
                <button class="btn btn-default action" data-action="clearLocalCache">{{translate 'Clear Local Cache'}}</button>
                {{else}}
                    <div style="margin-bottom: 10px;">
                        <span class="text-danger">
                        {{translate 'Cache is not enabled'}}
                        </span>
                    </div>
                {{/if}}

                <div class="hidden message-container margin-bottom"><span class="text-success"></span></div>
                <div>
                    <button class="btn btn-default action {{#if cacheIsEnabled}}hidden{{/if}}" data-action="returnToApplication">{{translate 'Return to Application'}}</button>
                </div>
            </div>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/about.tpl
<div class="page-header">
    <h3>{{translate 'About'}}</h3>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-body">
                <p>
                    <span class="text-bold text-soft">Version {{version}}</span>
                </p>
            </div>

        </div>
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="complex-text">
                    {{text}}
                </div>
            </div>
        </div>

    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/wysiwyg/modals/insert-link.tpl
<div class="panel panel-default no-side-margin">
    <div class="panel-body">
        <div class="cell form-group">
            <label class="control-label">{{labels.textToDisplay}}</label>
            <div class="field">
                <input class="form-control note-form-control note-input" type="text" data-name="text">
            </div>
        </div>
        <div class="cell form-group">
            <label class="control-label">{{labels.url}}</label>
            <div class="field">
                <input class="form-control note-form-control note-input" type="text" data-name="url">
            </div>
        </div>
        <div class="cell form-group">
            <label class="control-label">{{labels.openInNewWindow}}</label>
            <div class="field">
                <input class="form-checkbox" type="checkbox" data-name="openInNewWindow" checked>
            </div>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/wysiwyg/modals/insert-image.tpl
<div class="panel panel-default no-side-margin">
    <div class="panel-body">
        <div class="cell form-group">
            <label class="control-label">{{labels.selectFromFiles}}</label>
            <div class="field">
                <label class="attach-file-label" title="{{translate 'Attach File'}}" tabindex="0">
                    <span class="btn btn-default btn-icon"><span class="fas fa-paperclip"></span></span>
                    <input
                        type="file"
                        data-name="files"
                        accept="image/*"
                        tabindex="-1"
                        class="file pull-right"
                    >
                </label>
            </div>
        </div>
        <div class="cell form-group">
            <label class="control-label">{{labels.url}}</label>
            <div class="field">
                <div class="input-group">
                    <input
                        class="note-image-url form-control note-form-control note-input"
                        type="text"
                        data-name="url"
                    >
                    <span class="input-group-btn">
                        <button
                            class="btn btn-default disabled action"
                            disabled="disabled"
                            data-name="insert"
                            data-action="insert"
                        >{{translate 'Insert'}}</button>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/user-security/modals/two-factor-sms.tpl
<div class="panel no-side-margin">
    <div class="panel-body">
        <p class="p-info">
            {{translate 'choose2FaSmsPhoneNumber' category='messages' scope='User'}}
        </p>
        <p class="p-button">
            <button class="btn btn-default" data-action="sendCode">{{translate 'Send Code' scope='User'}}</button>
        </p>
        <p class="p-info-after hidden">
            {{translate 'enterCodeSentBySms' category='messages' scope='User'}}
        </p>
    </div>
</div>

<div class="record no-side-margin">{{{record}}}</div>

_delimiter_6nomlrqbt5x
res/templates/user-security/modals/two-factor-email.tpl
<div class="panel no-side-margin">
    <div class="panel-body">
        <p class="p-info">
            {{translate 'choose2FaEmailAddress' category='messages' scope='User'}}
        </p>
        <p class="p-button">
            <button class="btn btn-default" data-action="sendCode">{{translate 'Send Code' scope='User'}}</button>
        </p>
        <p class="p-info-after hidden">
            {{translate 'enterCodeSentInEmail' category='messages' scope='User'}}
        </p>
    </div>
</div>

<div class="record no-side-margin">{{{record}}}</div>

_delimiter_6nomlrqbt5x
res/templates/user-security/modals/totp.tpl
<div class="panel no-side-margin">
    <div class="panel-body">

        <p>{{translate 'verifyTotpCode' category='messages' scope='User'}}</p>

        <div style="margin-top: 20px; background: #FFF; padding: 20px;">
            <div class="qrcode"></div>
        </div>
    </div>
</div>

<div class="record no-side-margin">{{{record}}}</div>

_delimiter_6nomlrqbt5x
res/templates/user/password-change-request.tpl
<div class="container content">
    <div class="block-center">
        <div class="panel panel-default password-change">
            <div class="panel-heading">
                <h4 class="panel-title">{{translate 'Change Password' scope='User'}}</h4>
            </div>
            <div class="panel-body">
                {{#unless notFound}}
                <div class="row">
                    <div class="cell form-group col-sm-6">
                        <label
                            for="login"
                            class="control-label"
                        >{{translate 'newPassword' category='fields' scope='User'}}</label>
                        <div class="field" data-name="password">{{{password}}}</div>
                    </div>
                    <div class="cell form-group col-sm-6">
                        <label class="control-label"></label>
                        <div class="field" data-name="generatePassword">{{{generatePassword}}}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="cell form-group col-sm-6">
                        <label
                            for="login"
                            class="control-label"
                        >{{translate 'newPasswordConfirm' category='fields' scope='User'}}</label>
                        <div class="field" data-name="passwordConfirm">{{{passwordConfirm}}}</div>
                    </div>
                    <div class="cell form-group col-sm-6">
                        <label class="control-label"></label>
                        <div class="field" data-name="passwordPreview">{{{passwordPreview}}}</div>
                    </div>
                </div>
                <div>
                    <button
                        type="button"
                        class="btn btn-danger btn-s-wide"
                        id="btn-submit"
                    >{{translate 'Submit'}}</button>
                </div>
                {{else}}
                <p class="complex-text">{{complexText notFoundMessage}}</p>
                {{/unless}}
            </div>
        </div>
        <div class="msg-box hidden"></div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/user/modals/access.tpl
<div class="panel panel-default no-side-margin">
    <div class="panel-body">
        <div class="row">
            {{#each valuePermissionDataList}}
                <div class="cell col-sm-3 form-group" data-name="{{name}}">
                    <label class="control-label" data-name="{{name}}">{{translate name category="fields" scope="Role"}}</label>
                    <div class="field" data-name="{{name}}">
                        <span class="text-{{lookup ../styleMap value}}">
                            {{translateOption value scope="Role" field="assignmentPermission" translatedOptions=../levelListTranslation}}
                        </span>
                    </div>
                </div>
            {{/each}}
        </div>
    </div>
</div>

<div class="user-access-table no-side-margin">{{{table}}}</div>

_delimiter_6nomlrqbt5x
res/templates/user/fields/name/list.tpl
{{{avatar}}} {{value}}
_delimiter_6nomlrqbt5x
res/templates/user/fields/name/list-link.tpl
{{{avatar}}}<a
    href="#{{frontScope}}/view/{{model.id}}"
    class="link{{#if isOwn}} text-warning{{/if}}"
    data-id="{{model.id}}"
    title="{{value}}"
>{{value}}</a>

_delimiter_6nomlrqbt5x
res/templates/template/fields/variables/edit.tpl
<div class="input-group" style="table-layout: fixed; width: 100%;">
    <span class="input-group-item" style="width: 40%;">
        <select
            data-name="variables"
            class="main-element form-control radius-left"
        >{{{options attributeList '' translatedOptions=translatedOptions}}}</select>
    </span>
    <span class="input-group-item">
        <input data-name="copy" class="form-control" readonly="true">
    </span>
</div>

_delimiter_6nomlrqbt5x
res/templates/template/fields/variables/detail.tpl

_delimiter_6nomlrqbt5x
res/templates/stream/panel.tpl
<div class="form-group post-container{{#if postDisabled}} hidden{{/if}}">
    <div class="textarea-container">{{{postField}}}</div>
    <div class="buttons-panel margin hide floated-row clearfix">
        <div>
            <button class="btn btn-primary btn-xs-wide post">{{translate 'Post'}}</button>
            {{~#if allowInternalNotes~}}
                <span
                    style="cursor: pointer;"
                    class="internal-mode-switcher{{#if isInternalNoteMode}} enabled{{/if}} action"
                    data-action="switchInternalMode"
                    title="{{translate 'internalPost' category='messages'}}"
                >
                    <span class="fas fa-lock"></span>
                </span>
            {{~/if~}}
        </div>
        <div class="attachments-container">
            {{{attachments}}}
        </div>
        <a role="button" tabindex="-1" class="text-muted pull-right stream-post-info">
            <span class="fas fa-info-circle"></span>
        </a>
        <a
            role="button"
            tabindex="0"
            class="text-muted pull-right stream-post-preview hidden action"
            title="{{translate 'Preview'}}"
            data-action="preview"
        >
            <span class="fas fa-eye"></span>
        </a>
    </div>
</div>
{{#if hasPinned}}
    <div class="list-container" data-role="pinned">{{{pinnedList}}}</div>
{{/if}}
<div class="list-container" data-role="stream">{{{list}}}</div>

_delimiter_6nomlrqbt5x
res/templates/stream/row-actions/default.tpl
    {{#if isEnabled}}
    <div class="list-row-buttons pull-right right">
        {{#if acl.edit}}
        <div class="btn-group">
        <button
            type="button"
            class="btn btn-link btn-sm dropdown-toggle"
            data-toggle="dropdown"
        >
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu pull-right">
            {{#if isEditable}}
            <li><a
                role="button"
                class="action"
                tabindex="0"
                data-action="quickEdit"
                data-id="{{model.id}}"
                data-no-full-form="true"
            >{{translate 'Edit'}}</a></li>
            {{/if}}
            {{#if isRemovable}}
            <li><a
                role="button"
                class="action"
                tabindex="0"
                data-action="quickRemove"
                data-id="{{model.id}}"
            >{{translate 'Remove'}}</a></li>
            {{/if}}
        </ul>
        </div>
        {{/if}}
    </div>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/stream/record/edit.tpl
<div class="panel panel-default">
    <div class="panel-body panel-body-form">
        <div class="row">
            <div class="cell col-sm-12 form-group" data-name="post">
                <div class="field" data-name="post">{{{postField}}}</div>
            </div>
        </div>
        <div class="row post-control">
            <div class="col-sm-7">
                <div class="cell floated-row form-group clearfix">
                    <div>
                        <div class="field" style="display: inline-block;" data-name="attachments">{{{attachmentsField}}}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-5">
               <div class="cell form-group" data-name="targetType">
                    <label class="control-label">{{translate 'to' category='otherFields' scope='Note'}}</label>
                    <div class="field" data-name="targetType">{{{targetTypeField}}}</div>
                </div>
                <div class="cell form-group" data-name="users">
                    <div class="field" data-name="users">{{{usersField}}}</div>
                </div>
                <div class="cell form-group" data-name="teams">
                    <div class="field" data-name="teams">{{{teamsField}}}</div>
                </div>
                <div class="cell form-group" data-name="portals">
                    <div class="field" data-name="portals">{{{portalsField}}}</div>
                </div>
            </div>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/stream/notes/update.tpl
{{#unless noEdit}}
<div class="pull-right right-container">
{{{right}}}
</div>
{{/unless}}

<div class="stream-head-container">
    <div class="pull-left">
        {{{avatar}}}
    </div>
    <div class="stream-head-text-container">
        {{#if iconHtml}}{{{iconHtml}}}{{/if}}<span class="text-muted message">{{{message}}}</span>
    </div>
</div>

{{#if statusText}}
    <div class="stream-post-container">
        <span class="label label-state label-{{statusStyle}}">{{statusText}}</span>
    </div>
{{/if}}

{{#if fieldDataList.length}}
    <div class="stream-details-container">
        <a
            role="button"
            tabindex="0"
            data-action="expandDetails"
            class="text-muted no-underline"
        ><span class="fas fa-chevron-down text-soft" data-role="icon"></span>
            <span style="user-select: none"> </span>
            <span class="fields small">{{fieldsString}}</span>
        </a>
    </div>
{{/if}}

<div class="hidden details stream-details-container">
    <table class="table audited-summary-table">
        <tbody>
        {{#each fieldDataList}}
            <tr class="row" data-name="{{field}}">
                <td style="width: 30%">
                    <span class="">{{label}}</span>
                </td>
                <td style="width: 30%" class="cell-was">
                    {{#unless noValues}}
                        {{{var was ../this}}}
                    {{/unless}}
                </td>
                <td style="width: 10%; text-align: center;">
                    {{#unless noValues}}
                        <span class="text-muted small fas fa-arrow-right"></span>
                    {{/unless}}
                </td>
                <td style="width: 30%" class="cell-became">
                    {{#unless noValues}}
                        {{{var became ../this}}}
                    {{/unless}}
                </td>
            </tr>
        {{/each}}
        </tbody>
    </table>
</div>

<div class="stream-date-container">
    <a class="text-muted small" href="#Note/view/{{model.id}}">{{{createdAt}}}</a>
</div>

_delimiter_6nomlrqbt5x
res/templates/stream/notes/status.tpl
{{#unless noEdit}}
<div class="pull-right right-container">
    {{{right}}}
</div>
{{/unless}}

<div class="stream-head-container">
    <div class="pull-left">
        {{{avatar}}}
    </div>
    <div class="stream-head-text-container">
        {{#if iconHtml}}{{{iconHtml}}}{{/if}}<span class="text-muted message">{{{message}}}</span>
    </div>
</div>

<div class="stream-post-container">
    <span class="label label-state label-{{style}}">{{statusText}}</span>
</div>

<div class="stream-date-container">
    <a class="text-muted small" href="#Note/view/{{model.id}}">{{{createdAt}}}</a>
</div>

_delimiter_6nomlrqbt5x
res/templates/stream/notes/post.tpl
{{#unless noEdit}}
<div class="pull-right right-container">
{{{right}}}
</div>
{{/unless}}

<div class="stream-head-container">
    <div class="pull-left">
        {{{avatar}}}
        {{#if isInternal}}
        <div class="internal-badge">
            <span class="fas fa-lock small" title="{{translate 'internalPostTitle' category='messages'}}"></span>
        </div>
        {{/if}}
    </div>

    <div class="stream-head-text-container">
        <span class="text-muted message">{{{message}}}</span>
    </div>
</div>

{{#if showPost}}
<div class="stream-post-container">
    <span class="cell cell-post">{{{post}}}</span>
</div>
{{/if}}

{{#if showAttachments}}
<div class="stream-attachments-container">
    <span class="cell cell-attachments">{{{attachments}}}</span>
</div>
{{/if}}

<div class="stream-date-container">
    <a class="text-muted small" href="#Note/view/{{model.id}}">{{{createdAt}}}</a>
    {{#if isPinned}}
        <span class="fas fa-map-pin fa-sm pin-icon" title="{{translate 'Pinned' scope='Note'}}"></span>
    {{/if}}
    <div class="reactions-container">{{{reactions}}}</div>
</div>

_delimiter_6nomlrqbt5x
res/templates/stream/notes/email-received.tpl
{{#unless noEdit}}
<div class="pull-right right-container cell-buttons">
{{{right}}}
</div>
{{/unless}}

<div class="stream-head-container">
    <div class="pull-left">
        {{{avatar}}}
    </div>
    <div class="stream-head-text-container">
        <span
            class="{{emailIconClassName}} text-muted action icon"
            style="cursor: pointer;"
            title="{{translate 'View'}}"
            data-action="quickView"
            data-id="{{emailId}}"
            data-scope="Email"
        ></span><span class="message text-muted">{{{message}}}</span>
        {{#if hasExpand}}
            <a
                role="button"
                tabindex="0"
                data-action="expandDetails"
            ><span class="fas {{#if detailsIsShown}} fa-chevron-up {{else}} fa-chevron-down {{/if}} "></span></a>
        {{/if}}
    </div>
</div>

<div class="stream-subject-container">
    <span class="cell cell-name"><a
        href="#Email/view/{{emailId}}"
        data-id="{{emailId}}"
        data-scope="Email"
    >{{emailName}}</a></span>
</div>

{{#if detailsIsShown}}
    {{#if bodyField}}
        <div class="details stream-details-container">
            <div class="cell" data-name="body">{{{bodyField}}}</div>
            {{#if attachmentsField}}
                <div data-name="attachments" class="cell margin-top">{{{attachmentsField}}}</div>
            {{/if}}
        </div>
    {{/if}}
{{/if}}

{{#if hasPost}}
<div class="stream-post-container">
    <span class="cell cell-post {{#if mutedPost}} text-muted {{/if}}">{{{post}}}</span>
</div>
{{/if}}

{{#if hasAttachments}}
<div class="stream-attachments-container">
    <span class="cell cell-attachments">{{{attachments}}}</span>
</div>
{{/if}}

<div class="stream-date-container">
    <a class="text-muted small" href="#Note/view/{{model.id}}">{{{createdAt}}}</a>
    {{#if isPinned}}
        <span class="fas fa-map-pin fa-sm pin-icon" title="{{translate 'Pinned' scope='Note'}}"></span>
    {{/if}}
</div>

_delimiter_6nomlrqbt5x
res/templates/stream/notes/create.tpl
{{#unless noEdit}}
<div class="pull-right right-container cell-buttons">
{{{right}}}
</div>
{{/unless}}

<div class="stream-head-container">
    <div class="pull-left">
        {{{avatar}}}
    </div>
    <div class="stream-head-text-container">
        {{#if iconHtml}}{{{iconHtml}}}{{/if}}<span class="text-muted message">{{{message}}}</span>
    </div>
</div>

{{#if statusText}}
    <div class="stream-post-container">
        <span class="label label-state label-{{statusStyle}}">{{statusText}}</span>
    </div>
{{/if}}

<div class="stream-date-container">
    <a class="text-muted small" href="#Note/view/{{model.id}}">{{{createdAt}}}</a>
</div>

_delimiter_6nomlrqbt5x
res/templates/stream/notes/create-related.tpl
{{#unless noEdit}}
<div class="pull-right right-container cell-buttons">
{{{right}}}
</div>
{{/unless}}

<div class="stream-head-container">
    <div class="pull-left">
        {{{avatar}}}
    </div>
    <div class="stream-head-text-container">
        {{#if iconHtml}}{{{iconHtml}}}{{/if}}<span class="text-muted message">{{{message}}}</span>
    </div>
</div>

<div class="stream-date-container">
    <a class="text-muted small" href="#Note/view/{{model.id}}">{{{createdAt}}}</a>
</div>

_delimiter_6nomlrqbt5x
res/templates/stream/notes/assign.tpl
{{#unless noEdit}}
<div class="pull-right right-container">
{{{right}}}
</div>
{{/unless}}

<div class="stream-head-container">
    <div class="pull-left">
        {{{avatar}}}
    </div>
    <div class="stream-head-text-container">
        {{#if iconHtml}}{{{iconHtml}}}{{/if}}<span class="text-muted message">{{{message}}}</span>
    </div>
</div>
<div class="stream-date-container">
    <a class="text-muted small" href="#Note/view/{{model.id}}">{{{createdAt}}}</a>
</div>

_delimiter_6nomlrqbt5x
res/templates/site/navbar.tpl
<div class="navbar navbar-inverse" role="navigation">
    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-action="toggleCollapsable">
            <span class="fas fa-bars"></span>
        </button>
        <div class="navbar-logo-container"
            ><a
                class="navbar-brand nav-link"
                href="#"
            ><img src="{{logoSrc}}" class="logo" alt="logo"></a></div>
        <a role="button" class="side-menu-button"><span class="fas fa-bars"></span></a>
    </div>

    <div class="navbar-collapse navbar-body">
        <div class="navbar-left-container">
            <ul class="nav navbar-nav tabs">
                {{#each tabDefsList1}}
                    <li
                        data-name="{{name}}"
                        class="not-in-more tab{{#if isGroup}} tab-group dropdown{{/if}}{{#if isDivider}} tab-divider{{/if}}"
                    >
                        {{#if isDivider}}
                            <div class="{{aClassName}}"><span class="label-text">{{#if label}}{{label}}{{/if}}</span></div>
                        {{/if}}
                        {{#unless isDivider}}
                            <a
                                    {{#if link}}href="{{link}}"{{else}}role="button"{{/if}}
                                    class="{{aClassName}}"
                                    {{#if color}}style="border-color: {{color}}"{{/if}}
                                {{#if isGroup}}
                                    id="nav-tab-group-{{name}}"
                                    data-toggle="dropdown"
                                {{/if}}
                            >
                    <span class="short-label"{{#if label}} title="{{label}}"{{/if}}{{#if color}} style="color: {{color}}"{{/if}}>
                        {{#if iconClass}}
                            <span class="{{iconClass}}"></span>
                        {{else}}
                            {{#if colorIconClass}}
                                <span class="{{colorIconClass}}" style="color: {{color}}"></span>
                            {{/if}}
                            <span class="short-label-text">{{shortLabel}}</span>
                        {{/if}}
                    </span>
                                {{#if label}}
                                    <span class="full-label">{{label}}</span>
                                {{/if}}
                                {{#if html}}{{{html}}}{{/if}}

                                {{#if isGroup}}
                                    <span class="fas fa-caret-right group-caret"></span>
                                {{/if}}
                            </a>
                        {{/unless}}
                        {{#if isGroup}}
                            <ul class="dropdown-menu" role="menu" aria-labelledby="nav-tab-group-{{name}}">
                                {{#each itemList}}
                                    {{#if isDivider}}
                                        <li class="divider"></li>
                                    {{else}}
                                        <li data-name="{{name}}" class="in-group tab">
                                            <a
                                                    {{#if link}}href="{{link}}"{{else}}role="button"{{/if}}
                                                    class="{{aClassName}}"
                                                {{#if color}}
                                                    style="border-color: {{color}}"
                                                {{/if}}
                                                {{#if isGroup}}
                                                    id="nav-tab-group-{{name}}"
                                                    data-toggle="dropdown"
                                                {{/if}}
                                            >
                            <span class="short-label"{{#if color}} style="color: {{color}}"{{/if}}>
                                {{#if iconClass}}
                                    <span class="{{iconClass}}"></span>
                                {{else}}
                                    {{#if colorIconClass}}
                                        <span class="{{colorIconClass}}" style="color: {{color}}"></span>
                                    {{/if}}
                                    <span class="short-label-text">&nbsp;</span>
                                {{/if}}
                            </span>
                                                <span class="full-label">{{label}}</span>
                                            </a>
                                        </li>
                                    {{/if}}
                                {{/each}}
                            </ul>
                        {{/if}}
                    </li>
                {{/each}}
                <li class="dropdown more{{#unless tabDefsList2.length}} hidden{{/unless}}">
                    <a
                        id="nav-more-tabs-dropdown"
                        class="dropdown-toggle"
                        data-toggle="dropdown"
                        role="button"
                        tabindex="0"
                    ><span class="fas fa-ellipsis-h more-icon"></span></a>
                    <ul class="dropdown-menu more-dropdown-menu" role="menu" aria-labelledby="nav-more-tabs-dropdown">
                        {{#each tabDefsList2}}
                            <li
                                data-name="{{name}}"
                                class="in-more tab{{#if className}} {{className}}{{/if}}{{#if isGroup}} dropdown tab-group{{/if}}{{#if isDivider}} tab-divider{{/if}}"
                            >
                                {{#if isDivider}}
                                    <div class="{{aClassName}}{{#unless label}} no-text{{/unless}}"><span class="label-text">{{#if label}}{{label}}{{/if}}</span></div>
                                {{/if}}
                                {{#unless isDivider}}
                                    <a
                                            {{#if link}}href="{{link}}"{{else}}role="button"{{/if}}
                                            tabindex="0"
                                            class="{{aClassName}}"
                                        {{#if color}} style="border-color: {{color}}"{{/if}}
                                        {{#if isGroup}}
                                            id="nav-tab-group-{{name}}"
                                            data-toggle="dropdown"
                                        {{/if}}
                                    >
                            <span class="short-label"{{#if color}} style="color: {{color}}"{{/if}}>
                                {{#if iconClass}}
                                    <span class="{{iconClass}}"></span>
                                {{else}}
                                    {{#if colorIconClass}}
                                        <span class="{{colorIconClass}}" style="color: {{color}}"></span>
                                    {{/if}}
                                    <span class="short-label-text">&nbsp;</span>
                                {{/if}}
                            </span>
                                        {{#if label}}
                                            <span class="full-label">{{label}}</span>
                                        {{/if}}
                                        {{#if html}}{{{html}}}{{/if}}

                                        {{#if isGroup}}
                                            <span class="fas fa-caret-right group-caret"></span>
                                        {{/if}}
                                    </a>
                                {{/unless}}
                                {{#if isGroup}}
                                    <ul class="dropdown-menu" role="menu" aria-labelledby="nav-tab-group-{{name}}">
                                        {{#each itemList}}
                                            {{#if isDivider}}
                                                <li class="divider"></li>
                                            {{else}}
                                                <li data-name="{{name}}" class="in-group tab">
                                                    <a
                                                            {{#if link}}href="{{link}}"{{else}}role="button"{{/if}}
                                                            tabindex="0"
                                                            class="{{aClassName}}"
                                                        {{#if color}}
                                                            style="border-color: {{color}}"
                                                        {{/if}}
                                                        {{#if isGroup}}
                                                            id="nav-tab-group-{{name}}"
                                                            data-toggle="dropdown"
                                                        {{/if}}
                                                    >
                                    <span class="short-label"{{#if color}} style="color: {{color}}"{{/if}}>
                                        {{#if iconClass}}
                                            <span class="{{iconClass}}"></span>
                                        {{else}}
                                            {{#if colorIconClass}}
                                                <span class="{{colorIconClass}}" style="color: {{color}}"></span>
                                            {{/if}}
                                            <span class="short-label-text">&nbsp;</span>
                                        {{/if}}
                                    </span>
                                                        <span class="full-label">{{label}}</span>
                                                    </a>
                                                </li>
                                            {{/if}}
                                        {{/each}}
                                    </ul>
                                {{/if}}
                            </li>
                        {{/each}}
                    </ul>
                </li>
            </ul>
            <a class="minimizer hidden" role="button" tabindex="0">
                <span class="fas fa-chevron-right right"></span>
                <span class="fas fa-chevron-left left"></span>
            </a>
        </div>
        <div class="navbar-right-container">
            <ul class="nav navbar-nav navbar-right">
                {{#each itemDataList}}
                    <li class="{{class}}" data-item="{{name}}">{{{var key ../this}}}</li>
                {{/each}}
                <li class="dropdown menu-container">
                    <a
                        id="nav-menu-dropdown"
                        class="dropdown-toggle"
                        data-toggle="dropdown"
                        role="button"
                        tabindex="0"
                        title="{{translate 'Menu'}}"
                    ><span class="fas fa-ellipsis-v icon"></span></a>
                    <ul class="dropdown-menu" role="menu" aria-labelledby="nav-menu-dropdown">
                        {{#each menuDataList}}
                            {{#unless divider}}
                                <li><a
                                    {{#if name}}data-name="{{name}}"{{/if}}
                                    {{#if link}}href="{{link}}"{{else}}role="button"{{/if}}
                                    tabindex="0"
                                    class="nav-link{{#if handler}} action{{/if}}"
                                >{{#if html}}{{{html}}}{{else}}{{label}}{{/if}}</a></li>
                            {{else}}
                                <li class="divider"></li>
                            {{/unless}}
                        {{/each}}
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/site/master.tpl
<header id="header">{{{header}}}</header>
<div id="content" class="container content">
    <div id="main" tabindex="-1">{{{main}}}</div>
</div>
<footer id="footer">{{{footer}}}</footer>
<div class="collapsed-modal-bar"></div>

_delimiter_6nomlrqbt5x
res/templates/site/header.tpl
<div id="navbar">{{{navbar}}}</div>

_delimiter_6nomlrqbt5x
res/templates/site/footer.tpl
<p class="credit small">&copy; 2025
<a
    href="https://www.espocrm.com"
    title="Powered by EspoCRM"
    rel="noopener" target="_blank"
    tabindex="-1"
>EspoCRM, Inc.</a></p>

_delimiter_6nomlrqbt5x
res/templates/settings/fields/dashboard-layout/edit.tpl
<div class="button-container clearfix">
    <button
        class="btn btn-default btn-icon"
        data-action="editTabs"
        title="{{translate 'Edit Dashboard'}}"
    ><span class="fas fa-pencil-alt fa-sm"></span></button>
    <button
        class="btn btn-default btn-icon"
        data-action="addDashlet"
        title="{{translate 'Add Dashlet'}}"
    ><span class="fas fa-plus"></span></button>

    {{#ifNotEqual dashboardLayout.length 1}}
    <div class="btn-group pull-right dashboard-tabs">
        {{#each dashboardLayout}}
            <button
                class="btn btn-text{{#ifEqual @index ../currentTab}} active{{/ifEqual}}"
                data-action="selectTab"
                data-tab="{{@index}}"
            >{{name}}</button>
        {{/each}}
    </div>
    {{/ifNotEqual}}
</div>

<div class="grid-stack grid-stack-12"></div>

_delimiter_6nomlrqbt5x
res/templates/settings/fields/dashboard-layout/detail.tpl
{{#if isEmpty}}
<span class="none-value">{{translate 'None'}}</span>
{{/if}}

<div class="button-container clearfix">
    {{#ifNotEqual dashboardLayout.length 1}}
    <div class="btn-group pull-right dashboard-tabs">
        {{#each dashboardLayout}}
        <button
            class="btn btn-text{{#ifEqual @index ../currentTab}} active{{/ifEqual}}"
            data-action="selectTab"
            data-tab="{{@index}}"
        >{{name}}</button>
        {{/each}}
    </div>
    {{/ifNotEqual}}
</div>

<div class="grid-stack grid-stack-12"></div>

_delimiter_6nomlrqbt5x
res/templates/settings/fields/currency-rates/edit.tpl
{{#each rateValues}}
    <div class="input-group">
        <span class="input-group-addon radius-left" style="width: 25%">1 {{@key}} = </span>
        <span class="input-group-item">
            <input
                class="form-control"
                type="text"
                data-currency="{{@key}}"
                value="{{./this}}"
                style="text-align: right;"
                readonly="readonly"
            >
        </span>
        <span class="input-group-addon radius-right" style="width: 22%">{{../baseCurrency}}</span>
    </div>
{{/each}}

_delimiter_6nomlrqbt5x
res/templates/search/filter.tpl
<div class="form-group">
    <a
        role="button"
        tabindex="0"
        class="remove-filter pull-right"
        data-name="{{name}}"
    >{{#unless notRemovable}}<i class="fas fa-times"></i>{{/unless}}</a>
    <label class="control-label" data-name="{{name}}">{{translate name category='fields' scope=scope}}</label>
    <div class="field" data-name="{{name}}">{{{field}}}</div>
</div>

_delimiter_6nomlrqbt5x
res/templates/scheduled-job/cronjob.tpl
<div class="cronjob well">
    <div class="message">&nbsp;</div>

    <div class="margin-top">
        <code class="command">&nbsp;</code>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/role/table.tpl

<div class="button-container negate-no-side-margin">
    <input
        type="text"
        maxlength="64"
        placeholder="{{translate 'Search'}}"
        data-name="quick-search"
        class="form-control"
        spellcheck="false"
    >
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">{{translate 'Scope Level' scope='Role'}}</h4>
    </div>
    <div class="panel-body">
        <div class="no-margin">
            <table class="table table-bordered-inside no-margin scope-level">
                <tr>
                    <th></th>
                    <th style="width: 20%">{{translate 'Access' scope='Role'}}</th>
                    {{#each actionList}}
                        <th style="width: 11%">{{translate this scope='Role' category='actions'}}</th>
                    {{/each}}
                </tr>
                {{#each tableDataList}}
                    {{#unless this}}
                        <tr data-name="_" class="item-row">
                            <td>&#8203;</td><td></td>
                        </tr>
                    {{else}}
                        <tr data-name="{{name}}" class="item-row">
                            <td><b>{{translate name category='scopeNamesPlural'}}</b></td>

                            <td data-name="{{name}}">{{{lookup ../this name}}}</td>

                            {{#ifNotEqual type 'boolean'}}
                                {{#each list}}
                                    <td data-name="{{name}}">
                                        <div
                                            data-name="{{name}}"
                                            class="cell {{#if (lookup ../../hiddenFields name) }} hidden  {{/if}} "
                                        >
                                            {{#ifNotEqual access 'not-set'}}
                                                {{{lookup ../../this name}}}
                                            {{/ifNotEqual}}
                                        </div>
                                    </td>
                                {{/each}}
                            {{/ifNotEqual}}
                        </tr>
                    {{/unless}}
                {{/each}}
            </table>

            <div class="sticky-header-scope hidden sticky-head">
                <table class="table borderless no-margin">
                    <tr>
                        <th></th>
                        <th style="width: 20%">{{translate 'Access' scope='Role'}}</th>
                        {{#each actionList}}
                            <th style="width: 11%">{{translate this scope='Role' category='actions'}}</th>
                        {{/each}}
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>


{{#if hasFieldLevelData}}
<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">{{translate 'Field Level' scope='Role'}}</h4>
    </div>
    <div class="panel-body">
        <div class="no-margin">
            <table class="table table-bordered-inside no-margin field-level">
                <tr>
                    <th></th>
                    <th style="width: 20%"></th>
                    {{#each fieldActionList}}
                        <th style="width: 11%">{{translate this scope='Role' category='actions'}}</th>
                    {{/each}}
                    <th style="width: 33%"></th>
                </tr>
                {{#each fieldTableDataList}}
                    {{#if list.length}}
                    <tr data-name="{{name}}" class="item-row accented">
                        <td><b>{{translate name category='scopeNamesPlural'}}</b></td>
                        <td></td>
                        <td colspan="3"></td>
                    </tr>
                    {{/if}}
                    {{#each list}}
                    <tr data-name="{{../name}}" class="item-row">
                        <td></td>
                        <td>{{translate name category='fields' scope=../name}}</td>
                        {{#each list}}
                        <td>
                            <div data-name="{{name}}">{{{lookup ../../../this name}}}</div>
                        </td>
                        {{/each}}
                        <td colspan="3"></td>
                    </tr>
                    {{/each}}
                {{/each}}
            </table>

            <div class="sticky-header-field hidden sticky-head">
                <table class="table borderless no-margin">
                    <tr>
                        <th></th>
                        <th style="width: 20%"></th>
                        {{#each fieldActionList}}
                            <th style="width: 11%">{{translate this scope='Role' category='actions'}}</th>
                        {{/each}}
                        <th style="width: 33%"></th>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/role/table-edit.tpl

<div class="button-container">
    <input
        type="text"
        maxlength="64"
        placeholder="{{translate 'Search'}}"
        data-name="quick-search"
        class="form-control"
        spellcheck="false"
    >
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">{{translate 'Scope Level' scope='Role'}}</h4>
    </div>
    <div class="panel-body">
        <div class="no-margin">
            <table class="table table-bordered-inside no-margin scope-level">
                <tr>
                    <th></th>
                    <th style="width: 20%">{{translate 'Access' scope='Role'}}</th>
                    {{#each actionList}}
                        <th style="width: 11%">{{translate this scope='Role' category='actions'}}</th>
                    {{/each}}
                </tr>
                {{#each tableDataList}}
                    {{#unless this}}
                        <tr data-name="_" class="item-row">
                            <td><div class="detail-field-container">&#8203;</div></td><td></td>
                        </tr>
                    {{else}}
                        <tr data-name="{{name}}" class="item-row">
                            <td>
                                <div class="detail-field-container">
                                    <b>{{translate name category='scopeNamesPlural'}}</b>
                                </div>
                            </td>
                            <td data-name="{{name}}">{{{lookup ../this name}}}</td>

                            {{#ifNotEqual type 'boolean'}}
                                {{#each list}}
                                    <td data-name="{{name}}">
                                        <div
                                            data-name="{{name}}"
                                            class=" {{#if (lookup ../../hiddenFields name) }} hidden  {{/if}} "
                                        >{{{lookup ../../this name}}}</div>
                                    </td>
                                {{/each}}
                            {{/ifNotEqual}}
                        </tr>
                    {{/unless}}
                {{/each}}
            </table>

            <div class="sticky-header-scope hidden sticky-head">
                <table class="table borderless no-margin">
                    <tr>
                        <th></th>
                        <th style="width: 20%">{{translate 'Access' scope='Role'}}</th>
                        {{#each actionList}}
                            <th style="width: 11%">{{translate this scope='Role' category='actions'}}</th>
                        {{/each}}
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

{{#if fieldTableDataList.length}}
<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">{{translate 'Field Level' scope='Role'}}</h4>
    </div>
    <div class="panel-body">
        <div class="no-margin" style="margin-bottom: 0;">
            <table class="table table-bordered-inside table-bottom-bordered no-margin field-level">
                <tr>
                    <th></th>
                    <th style="width: 20%"></th>
                    {{#each fieldActionList}}
                        <th style="width: 11%">{{translate this scope='Role' category='actions'}}</th>
                    {{/each}}
                    <th style="width: 33%"></th>
                </tr>
                {{#each fieldTableDataList}}
                    <tr data-name="{{name}}" class="item-row accented">
                        <td>
                            <div class="detail-field-container">
                                <b>{{translate name category='scopeNamesPlural'}}</b>
                            </div>
                        </td>
                        <td><button
                            type="button"
                            class="btn btn-link action"
                            data-action="addField"
                            data-scope="{{name}}"
                            title="{{translate 'Add Field'}}"
                            ><span class="fas fa-plus fa-sm"></span></button></td>
                        <td colspan="3"></td>
                    </tr>
                    {{#each list}}
                    <tr data-name="{{../name}}" class="item-row">
                        <td></td>
                        <td>
                            <div class="detail-field-container">
                                <span>{{translate name category='fields' scope=../name}}</span>
                            </div>
                        </td>
                        {{#each list}}
                        <td>
                            <div data-name="{{name}}">{{{lookup ../../../this name}}}</div>
                        </td>
                        {{/each}}
                        <td colspan="2">
                            <a
                                role="button"
                                tabindex="0"
                                class="btn btn-link action"
                                title="{{translate 'Remove'}}"
                                data-action="removeField"
                                data-field="{{name}}"
                                data-scope="{{../name}}"
                                ><span class="fas fa-minus fa-sm"></span></a>
                        </td>
                    </tr>
                    {{/each}}
                {{/each}}
            </table>

            <div class="sticky-header-field hidden sticky-head">
                <table class="table borderless no-margin">
                    <tr>
                        <th></th>
                        <th style="width: 20%"></th>
                        {{#each fieldActionList}}
                            <th style="width: 11%">{{translate this scope='Role' category='actions'}}</th>
                        {{/each}}
                        <th style="width: 33%"></th>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/role/record/panels/side.tpl
<span class="text-danger">{{translate 'changesAfterClearCache' scope='Role' category='messages'}}</span>

_delimiter_6nomlrqbt5x
res/templates/role/modals/add-field.tpl
<div class="button-container negate-no-side-margin">
    <input
        type="text"
        maxlength="64"
        placeholder="{{translate 'Search'}}"
        data-name="quick-search"
        class="form-control"
        spellcheck="false"
    >
</div>
<div class="list-container">
    <div class="list">
        <table class="table fields-table">
            {{#each dataList}}
                <tr data-name="{{name}}">
                    <td class="r-checkbox" style="width: 40px;">
                        <span class="record-checkbox-container">
                            <input
                                type="checkbox"
                                data-name="{{name}}"
                                class="record-checkbox form-checkbox form-checkbox-small"
                            >
                        </span>
                    </td>
                    <td>
                        <a
                            role="button"
                            tabindex="0"
                            data-action="addField"
                            data-name="{{name}}"
                        >{{label}}</a>
                    </td>
                </tr>
            {{/each}}
        </table>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/record/side.tpl
{{#each panelList}}
    {{#if isRightAfterDelimiter}}
        <div class="panels-show-more-delimiter">
            <a
                role="button"
                tabindex="0"
                data-action="showMorePanels"
                title="{{translate 'Show more'}}"
            >
                <span class="fas fa-ellipsis-h fa-lg"></span>
            </a>
        </div>
    {{/if}}
    <div
        class="panel panel-{{#if style}}{{style}}{{else}}default{{/if}} panel-{{name}}{{#if label}} headered{{/if}}{{#if hidden}} hidden{{/if}}{{#if sticked}} sticked{{/if}}"
        data-name="{{name}}"
        data-style="{{#if style}}{{style}}{{/if}}"
        data-tab="{{tabNumber}}"
    >
        {{#if label}}
        <div class="panel-heading">
            <div class="pull-right btn-group panel-actions-container">{{{var actionsViewKey ../this}}}</div>

            <h4 class="panel-title">
                {{#unless notRefreshable}}
                <span
                    style="cursor: pointer; user-select: none;"
                    class="action"
                    title="{{translate 'clickToRefresh' category='messages'}}"
                    data-action="refresh" data-panel="{{name}}"
                >
                {{/unless}}
                {{#if titleHtml}}
                    {{{titleHtml}}}
                {{else}}
                    {{title}}
                {{/if}}
                {{#unless notRefreshable}}
                </span>
                {{/unless}}
            </h4>
        </div>
        {{/if}}

        <div class="panel-body{{#if isForm}} panel-body-form{{/if}}" data-name="{{name}}">
            {{{var name ../this}}}
        </div>
    </div>
{{/each}}

_delimiter_6nomlrqbt5x
res/templates/record/search.tpl

<div class="row search-row">
    <div class="form-group{{#if isWide}} col-lg-7{{/if}} col-md-8 col-sm-9">
        <div class="input-group">
            <div class="input-group-btn left-dropdown{{#unless leftDropdown}} hidden{{/unless}}">
                <button
                    type="button"
                    class="btn btn-default dropdown-toggle filters-button"
                    title="{{translate 'Filter'}}"
                    data-toggle="dropdown"
                    tabindex="0"
                >
                    <span class="filters-label"></span>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu pull-left filter-menu">
                    {{#unless primaryFiltersDisabled}}
                        <li>
                            <a
                                class="preset"
                                tabindex="0"
                                role="button"
                                data-name=""
                                data-action="selectPreset"
                            ><div>{{translate 'all' category='presetFilters' scope=entityType}}</div></a>
                        </li>
                        {{#each presetFilterList}}
                        <li>
                            <a
                                class="preset"
                                tabindex="0"
                                role="button"
                                data-name="{{name}}"
                                data-action="selectPreset"
                            >
                                <div class="{{#if style}}text-{{style}}{{/if}}">
                                {{~#if label}}{{label}}{{else}}{{translate name category='presetFilters' scope=../entityType}}{{/if~}}
                                </div>
                            </a>
                        </li>
                        {{/each}}
                        <li class="divider preset-control hidden"></li>

                        <li class="preset-control remove-preset hidden">
                            <a tabindex="0" role="button" data-action="removePreset">{{translate 'Remove Filter'}}</a>
                        </li>
                        <li class="preset-control save-preset hidden">
                            <a tabindex="0" role="button" data-action="savePreset">{{translate 'Save Filter'}}</a>
                        </li>

                        {{#if boolFilterList.length}}
                            <li class="divider"></li>
                        {{/if}}
                    {{/unless}}

                    {{#each boolFilterList}}
                        <li class="checkbox">
                            <label>
                                <input
                                    type="checkbox"
                                    data-role="boolFilterCheckbox"
                                    data-name="{{./this}}"
                                    class="form-checkbox form-checkbox-small"
                                    {{#ifPropEquals ../bool this true}}checked{{/ifPropEquals}}
                                > {{translate this scope=../entityType category='boolFilters'}}
                            </label></li>
                    {{/each}}
                </ul>
            </div>
            <input
                type="search"
                class="form-control text-filter"
                data-name="textFilter"
                value="{{textFilter}}"
                tabindex="0"
                autocomplete="espo-text-search"
                spellcheck="false"
                {{#if textFilterDisabled}}disabled="disabled"{{/if}}
            >
            <div class="input-group-btn">
                <button
                    type="button"
                    class="btn btn-default search btn-icon btn-icon-x-wide"
                    data-action="search"
                    tabindex="0"
                    title="{{translate 'Search'}}"
                >
                    <span class="fas fa-search"></span>
                </button>
            </div>
            <div class="input-group-btn">
                <button
                    type="button"
                    class="btn btn-text btn-icon btn-icon-wide dropdown-toggle add-filter-button"
                    data-toggle="dropdown"
                    tabindex="0"
                >
                    <span class="fas fa-ellipsis-v"></span>
                </button>
                <button
                    type="button"
                    class="btn btn-text btn-icon btn-icon-wide"
                    data-action="reset"
                    title="{{translate 'Reset'}}"
                    tabindex="0"
                    style="visibility: hidden;"
                >
                    <span class="fas fa-times"></span>
                </button>
                <ul class="dropdown-menu pull-right filter-list">
                    <li class="dropdown-header">{{translate 'Add Field'}}</li>
                    {{#if hasFieldQuickSearch}}
                    <li class="quick-search-list-item">
                        <input class="form-control field-filter-quick-search-input">
                    </li>
                    {{/if}}
                    {{#each filterFieldDataList}}
                        <li
                            data-name="{{name}}"
                            class="filter-item {{#if checked}} hidden{{/if}}"
                        ><a
                            role="button"
                            tabindex="0"
                            class="add-filter"
                            data-action="addFilter"
                            data-name="{{name}}"
                        >{{label}}</a></li>
                    {{/each}}
                </ul>
            </div>
        </div>
    </div>
    <div class="form-group{{#if isWide}} col-lg-5{{/if}} col-md-4 col-sm-3">
        {{#if hasViewModeSwitcher}}
        <div class="btn-group view-mode-switcher-buttons-group">
            {{#each viewModeDataList}}
            <button
                type="button"
                data-name="{{name}}"
                data-action="switchViewMode"
                class="btn btn-icon btn-text{{#ifEqual name ../viewMode}} active{{/ifEqual}}"
                tabindex="0"
                title="{{title}}"
            ><span class="{{iconClass}}"></span></button>
            {{/each}}
        </div>
        {{/if}}
    </div>
</div>

<div class="advanced-filters hidden grid-auto-fill-sm">
{{#each filterDataList}}
    <div class="filter filter-{{name}}" data-name="{{name}}">
        {{{var key ../this}}}
    </div>
{{/each}}
</div>

<div class="advanced-filters-apply-container{{#unless toShowApplyFiltersButton}} hidden{{/unless}}">
    <a role="button" tabindex="0" class="btn btn-default btn-sm" data-action="applyFilters">
        <span class="fas fa-search fa-sm"></span>
        <span class="text-apply{{#if toShowResetFiltersText}} hidden{{/if}}">{{translate 'Apply'}}</span>
        <span class="text-reset{{#unless toShowResetFiltersText}} hidden{{/unless}}">{{translate 'Reset'}}</span>
    </a>
</div>

_delimiter_6nomlrqbt5x
res/templates/record/panel-actions.tpl
{{#each buttonList}}
    <button
        type="button"
        class="btn btn-{{#if ../defs.style}}{{../defs.style}}{{else}}default{{/if}} btn-sm panel-action action{{#if hidden}} hidden{{/if}}"
        {{#if action}}data-action="{{action}}"{{/if}}
        {{#if name}}data-name="{{name}}"{{/if}}
        data-panel="{{../defs.name}}" {{#each data}} data-{{hyphen @key}}="{{./this}}"{{/each}}
        title="{{#if title}}{{translate title scope=../scope}}{{/if}}"
    >{{#if html}}{{{html}}}{{else}}{{#if text}}{{text}}{{else}}{{translate label scope=../scope}}{{/if}}{{/if}}</button>
{{/each}}

{{#if actionList}}
    <button
        type="button"
        class="btn btn-{{#if defs.style}}{{defs.style}}{{else}}default{{/if}} btn-sm dropdown-toggle"
        data-toggle="dropdown"
    ><span class="fas fa-ellipsis-h"></span></button>
    <ul class="dropdown-menu">
        {{#each actionList}}
            {{#if this}}
                {{dropdownItem
                    action
                    scope=../scope
                    label=label
                    labelTranslation=labelTranslation
                    html=html
                    title=title
                    text=text
                    hidden=hidden
                    disabled=disabled
                    data=data
                    link=link
                    className='panel-action'
                }}
            {{else}}
                {{#unless @first}}
                    {{#unless @last}}
                        <li class="divider"></li>
                    {{/unless}}
                {{/unless}}
            {{/if}}
        {{/each}}
    </ul>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/record/merge.tpl

<div class="merge">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th style="width: 20%"></th>
                {{#each dataList}}
                <th style="vertical-align: middle; width: 5%">
                    <input
                        type="radio"
                        name="check-all"
                        value="{{id}}"
                        data-id="{{id}}"
                        class="pull-right form-radio"
                    >
                </th>
                <th style="width: {{../width}}%">
                    <a href="#{{../scope}}/view/{{id}}" target="_BLANK" class="text-large">{{name}}</a>
                </th>
                {{/each}}
            </tr>
        </thead>
        <tbody>
            {{#if hasCreatedAt}}
            <tr>
                <td style="text-align: right">
                    {{translate 'createdAt' scope=scope category='fields'}}
                </td>
                {{#each dataList}}
                <td></td>
                <td data-id="{{id}}">
                    <div class="field" data-name="createdAt">
                        {{{var createdAtViewName ../this}}}
                    </div>
                </td>
                {{/each}}
            </tr>
            {{/if}}
            {{#each rows}}
            <tr>
                <td style="text-align: right">
                    {{translate name scope=../scope category='fields'}}
                </td>
                {{#each columns}}
                <td>
                    {{#unless isReadOnly}}
                    <input
                        type="radio"
                        name="{{../name}}"
                        value="{{id}}"
                        data-id="{{id}}"
                        class="pull-right field-radio form-radio"
                    >
                    {{/unless}}
                </td>
                <td data-id="{{id}}">
                    <div class="field" data-name="{{../name}}">
                        {{{var fieldVariable ../../this}}}
                    </div>
                </td>
                {{/each}}
            </tr>
            {{/each}}
        </tbody>
    </table>
    <div class="button-container">
        <div class="btn-group">
            <button class="btn btn-danger btn-xs-wide" data-action="merge">{{translate 'Merge'}}</button>
            <button class="btn btn-default btn-xs-wide" data-action="cancel">{{translate 'Cancel'}}</button>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/record/list.tpl
{{#if hasStickyBar}}
    <div class="sticked-bar list-sticky-bar hidden">
        {{#if displayActionsButtonGroup}}
            <div class="btn-group">
                <button
                    type="button"
                    class="btn btn-default btn-xs-wide dropdown-toggle actions-button hidden"
                    data-toggle="dropdown"
                >{{translate 'Actions'}} <span class="caret"></span>
                </button>
                <ul class="dropdown-menu actions-menu">
                    {{#each massActionDataList}}
                        {{#if this}}
                            <li {{#if hidden}}class="hidden"{{/if}}>
                                <a
                                    role="button"
                                    tabindex="0"
                                    data-action="{{name}}"
                                    class="mass-action"
                                >{{translate name category="massActions" scope=../scope}}</a>
                            </li>
                        {{else}}
                            {{#unless @first}}
                                {{#unless @last}}
                                    <li class="divider"></li>
                                {{/unless}}
                            {{/unless}}
                        {{/if}}
                    {{/each}}
                </ul>
            </div>
        {{/if}}

        {{#if hasPagination}}
            {{{paginationSticky}}}
        {{/if}}
    </div>
{{/if}}

{{#if topBar}}
    <div class="list-buttons-container clearfix">
        {{#if displayActionsButtonGroup}}
            <div class="btn-group actions">
                {{#if massActionDataList}}
                    <button
                        type="button"
                        class="btn btn-default btn-xs-wide dropdown-toggle actions-button hidden"
                        data-toggle="dropdown"
                    >{{translate 'Actions'}} <span class="caret"></span></button>
                {{/if}}
                {{#if buttonList.length}}
                    {{#each buttonList}}
                        {{button
                            name
                            scope=../scope
                            label=label
                            style=style
                            hidden=hidden
                            class='list-action-item'
                        }}
                    {{/each}}
                {{/if}}

                <div class="btn-group">
                    {{#if dropdownItemList.length}}
                        <button
                            type="button"
                            class="btn btn-text dropdown-toggle dropdown-item-list-button"
                            data-toggle="dropdown"
                        ><span class="fas fa-ellipsis-h"></span></button>
                        <ul class="dropdown-menu pull-left">
                            {{#each dropdownItemList}}
                                {{#if this}}
                                    <li class="{{#if hidden}}hidden{{/if}}">
                                        <a
                                            role="button"
                                            tabindex="0"
                                            class="action list-action-item"
                                            data-action="{{name}}"
                                            data-name="{{name}}"
                                        >{{#if html}}{{{html}}}{{else}}{{translate label scope=../entityType}}{{/if}}</a></li>
                                {{else}}
                                    {{#unless @first}}
                                        {{#unless @last}}
                                            <li class="divider"></li>
                                        {{/unless}}
                                    {{/unless}}
                                {{/if}}
                            {{/each}}
                        </ul>
                    {{/if}}
                </div>

                {{#if massActionDataList}}
                    <ul class="dropdown-menu actions-menu">
                        {{#each massActionDataList}}
                            {{#if this}}
                                <li {{#if hidden}}class="hidden"{{/if}}>
                                    <a
                                        role="button"
                                        tabindex="0"
                                        data-action="{{name}}"
                                        class="mass-action"
                                    >{{translate name category="massActions" scope=../scope}}</a></li>
                            {{else}}
                                {{#unless @first}}
                                    {{#unless @last}}
                                        <li class="divider"></li>
                                    {{/unless}}
                                {{/unless}}
                            {{/if}}
                        {{/each}}
                    </ul>
                {{/if}}
            </div>
        {{/if}}

        {{#if hasPagination}}
            {{{pagination}}}
        {{/if}}

        {{#if settings}}
            <div class="settings-container pull-right">{{{settings}}}</div>
        {{/if}}

        {{#if displayTotalCount}}
            <div class="text-muted total-count">
        <span
            title="{{translate 'Total'}}"
            class="total-count-span"
        >{{totalCountFormatted}}</span>
            </div>
        {{/if}}
    </div>
{{/if}}



{{#if collectionLength}}
    <div
        class="list {{#if showMoreActive}} has-show-more {{/if}}"
        data-scope="{{scope}}"
        tabindex="-1"
    >
        <table
            class="table {{#if hasColumnResize~}} column-resizable {{~/if}}"
        >
            {{#if header}}
            <thead>
                <tr>
                    {{#if checkboxes}}
                    <th
                        style="width: {{checkboxColumnWidth}}"
                        data-name="r-checkbox"
                        class="checkbox-cell"
                    >
                        <span
                            class="select-all-container"
                        ><input type="checkbox" class="select-all form-checkbox form-checkbox-small"></span>
                        {{#unless checkAllResultDisabled}}
                        <div class="btn-group checkbox-dropdown">
                            <a
                                class="btn btn-link btn-sm dropdown-toggle"
                                data-toggle="dropdown"
                                tabindex="0"
                                role="button"
                            >
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a
                                        role="button"
                                        tabindex="0"
                                        data-action="selectAllResult"
                                    >{{translate 'Select All Results'}}</a>
                                </li>
                            </ul>
                        </div>
                        {{/unless}}
                    </th>
                    {{/if}}
                    {{#each headerDefs}}
                    <th
                        style="{{#if width}}width: {{width}};{{/if}}{{#if align}} text-align: {{align}};{{/if}}"
                        class="{{#if className~}} {{className}} {{~/if}} field-header-cell"
                        {{#if name}}data-name="{{name}}"{{/if}}
                    >
                        {{#if this.isSortable}}
                            <a
                                role="button"
                                tabindex="0"
                                class="sort"
                                data-name="{{this.name}}"
                                title="{{translate 'Sort'}}"
                            >{{label}}</a>
                            {{#if this.isSorted}}
                                {{#unless this.isDesc}}
                                <span class="fas fa-chevron-down fa-sm"></span>
                                {{else}}
                                <span class="fas fa-chevron-up fa-sm"></span>
                                {{/unless}}
                            {{/if}}
                        {{else}}
                            {{#if html}}
                            {{{html}}}
                            {{else}}
                            {{label}}
                            {{/if}}
                        {{/if}}

                        {{#if resizable}}
                            <div class="column-resizer {{#if resizeOnRight}} column-resizer-right {{/if}}"></div>
                        {{/if}}
                    </th>
                    {{/each}}
                </tr>
            </thead>
            {{/if}}
            <tbody>
            {{#each rowDataList}}
                <tr
                    data-id="{{id}}"
                    class="list-row {{#if isStarred}} starred {{~/if}}"
                >{{{var id ../this}}}</tr>
            {{/each}}
            </tbody>
        </table>

        {{#if showMoreEnabled}}
            <div class="show-more{{#unless showMoreActive}} hidden{{/unless}}">
                <a
                    type="button"
                    role="button"
                    tabindex="0"
                    class="btn btn-default btn-block"
                    data-action="showMore"
                    {{#if showCount}}title="{{translate 'Total'}}: {{totalCountFormatted}}"{{/if}}
                >
                    {{#if showCount}}
                    <div class="pull-right text-muted more-count">{{moreCountFormatted}}</div>
                    {{/if}}
                    <span>{{translate 'Show more'}}</span>
                </a>
            </div>
        {{/if}}
    </div>
{{else}}
    {{#unless noDataDisabled}}
    <div class="no-data">{{translate 'No Data'}}</div>
    {{/unless}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/record/list-tree.tpl

{{#if topBar}}
<div class="list-buttons-container clearfix">
    {{#each buttonList}}
        {{button name scope=../scope label=label style=style}}
    {{/each}}
</div>
{{/if}}

{{#if noData}}
<div class="no-data">{{translate 'No Data'}}</div>
{{/if}}

<div
    class="list list-expanded list-tree {{#if noData}} hidden {{/if}}"
    {{#if isEditable}} data-editable="true" {{/if}}
>
    {{#if showRoot}}
    <div class="root-item">
    <a
        href="#{{scope}}"
        class="action link{{#if rootIsSelected}} text-bold{{/if}}"
        data-action="selectRoot"
    >{{rootName}}</a>
        {{#if hasExpandToggle}}
         <a
            role="button"
            data-role="expandButtonContainer"
            title="{{#if isExpanded}}{{translate 'Expanded'}}{{else}}{{translate 'Collapsed'}}{{/if}}"
            data-action="toggleExpandedFromNavigation"
            class="{{#if expandToggleInactive}} disabled {{/if}}"
        >
            {{#if isExpanded}}
                <span class="fas fa-level-down-alt fa-sm text-soft"></span>
            {{else}}
                <span class="fas fa-level-down-alt fa-rotate-270 fa-sm text-soft"></span>
            {{/if}}
        </a>
        {{/if}}
    </div>
    {{/if}}

    <ul class="list-group list-group-tree list-group-no-border">
    {{#each rowList}}
        <li data-id="{{./this}}" class="list-group-item">
        {{{var this ../this}}}
        </li>
    {{/each}}
    {{#unless createDisabled}}
    <li class="list-group-item">
        <div>
            <a
                role="button"
                tabindex="0"
                data-action="create"
                class="action small"
                title="{{translate 'Add'}}"
            ><span class="fas fa-plus"></span></a>
        </div>
    </li>
    {{/unless}}
    </ul>
</div>

_delimiter_6nomlrqbt5x
res/templates/record/list-tree-item.tpl
<div class="cell">
    <a
        role="button"
        tabindex="0"
        class="action{{#unless showFold}} hidden{{/unless}} small"
        data-action="fold"
        data-id="{{model.id}}"><span class="fas fa-chevron-down"></span></a>

    <a
        role="button"
        tabindex="0"
        class="action{{#unless showUnfold}} hidden{{/unless}} small"
        data-action="unfold"
        data-id="{{model.id}}"><span class="fas fa-chevron-right"></span></a>

    <span
        data-name="white-space"
        data-id="{{model.id}}"
        class="empty-icon{{#unless isEnd}} hidden{{/unless}}"
    >&nbsp;</span>

    {{#if isMovable}}
        <a
            role="button"
            class=""
            data-id="{{model.id}}"
            data-role="moveHandle"
            data-title="{{name}}"
        ><span class="fas fa-grip fa-sm"></span></a>
    {{/if}}

    <a
        href="#{{model.entityType}}/view/{{model.id}}"
        class="link{{#if isSelected}} text-bold{{/if}}"
        data-id="{{model.id}}"
        title="{{name}}"
        {{#unless readOnly}} draggable="false" {{/unless}}
    >{{name}}</a>

    {{#unless readOnly}}
     <a
         role="button"
         tabindex="0"
         class="action small remove-link hidden"
         data-action="remove"
         data-id="{{model.id}}"
         title="{{translate 'Remove'}}"
    >
        <span class="fas fa-times"></span>
    </a>
    {{/unless}}
</div>
<div class="children{{#unless isUnfolded}} hidden{{/unless}}">{{{children}}}</div>

_delimiter_6nomlrqbt5x
res/templates/record/list-pagination.tpl
<div class="btn-group pagination clearfix">
    <div class="btn-group">
        <a
            class="pagination-btn-middle btn btn-text dropdown-toggle"
            role="button"
            tabindex="0"
            data-toggle="dropdown"
            {{#unless noTotal}}title="{{translate 'Total'}}: {{total}}"{{/unless}}
        >{{#unless noData}}{{from}}–{{/unless}}{{to}}{{#unless noTotal}} / {{total}}{{/unless}}</a>
        <ul class="dropdown-menu pull-right">
            <li>
                <a
                    role="button"
                    tabindex="0"
                    data-page="first"
                    class="{{#unless previous}}disabled{{/unless}}"
                >{{translate 'First Page'}}</a>
            </li>
            <li>
                <a
                    role="button"
                    tabindex="0"
                    data-page="last"
                    class="{{#unless last}}disabled{{/unless}}"
                >{{translate 'Last Page'}}{{#if hasLastPageNumber}} · {{lastPageNumber}}{{/if}}</a>
            </li>
            {{#if hasGoToPage}}
                <li class="divider"></li>
                <li>
                    <div class="input-group page-input-group">
                        <span class="input-group-addon">{{translate 'Page'}}</span>
                        <input
                            class="form-control page-input"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            value="{{currentPageNumber}}"
                        >
                    </div>
                </li>
            {{/if}}
        </ul>
    </div>
    <a
        class="pagination-btn btn btn-text btn-icon{{#unless previous}} disabled{{/unless}}"
        role="button"
        tabindex="0"
        data-page="previous"
        title="{{translate 'Previous Page'}}"
    ><span class="fas fa-chevron-left"></span></a>
    <a
        class="pagination-btn btn btn-text btn-icon{{#unless next}} disabled{{/unless}}"
        role="button"
        tabindex="0"
        data-page="next"
        title="{{translate 'Next Page'}}"
    ><span class="fas fa-chevron-right"></span></a>
</div>

_delimiter_6nomlrqbt5x
res/templates/record/list-nested-categories.tpl
{{#unless isLoading}}
<div class="list-nested-categories">
    <div class="clearfix">
        <div class="btn-group pull-right">
            <a role="button" tabindex="0" class="dropdown-toggle btn btn-text" data-toggle="dropdown">
                <span class="fas fa-ellipsis-h"></span>
            </a>

            <ul class="dropdown-menu dropdown-menu-with-icons">
                {{#if showCreate}}
                    <li>
                        <a
                            href="{{createLink}}"
                            class="action"
                            data-action="createCategory"
                        >
                            <span class="fas fa-folder-plus fa-sm"></span><span class="item-text">{{createCategoryLabel}}</span>
                        </a>
                    </li>
                {{/if}}
                {{#if showEditLink}}
                <li>
                    <a
                        href="#{{scope}}"
                        class="action manage-categories-link"
                        data-action="manageCategories"
                    >
                        <span class="fas fa-folder-tree fa-sm"></span><span class="item-text">{{translate 'Manage Categories' scope=scope}}</span>
                    </a>
                </li>
                <li class="divider"></li>
                {{/if}}

                {{#if hasExpandedToggler}}
                    {{#if isExpanded}}
                        <li>
                            <a
                                role="button"
                                tabindex="0"
                                class="category-expanded-toggle-link action"
                                data-action="collapse"
                            ><span class="fas fa-level-up-alt fa-sm fa-flip-horizontal"></span><span class="item-text">{{translate 'Collapse'}}</span></a>
                        </li>
                    {{else}}
                        <li>
                            <a
                                role="button"
                                tabindex="0"
                                class="category-expanded-toggle-link action"
                                data-action="expand"
                            ><span class="fas fa-level-down-alt fa-sm"></span><span class="item-text">{{translate 'Expand'}}</span></a>
                        </li>
                    {{/if}}
                {{/if}}
                {{#unless isExpanded}}
                    <li>
                        <a
                            role="button"
                            tabindex="0"
                            class="navigation-toggle-link action"
                            data-action="toggleNavigationPanel"
                        >
                            <span class="fas fa-check check-icon pull-right {{#unless hasNavigationPanel}} hidden {{/unless}}"></span>
                            <div>
                                <span class="fas"></span><span class="item-text">{{translate 'Navigation Panel'}}</span>
                            </div>
                        </a>
                    </li>
                {{/unless}}
            </ul>
        </div>
        {{#if isExpandedResult}}
            <div class="input-text-block pull-right" style="user-select: none;">
                <span class="label label-default">{{translate 'Expanded'}}</span>
            </div>
        {{/if}}
        {{#if currentId}}
        <div class="category-item category-item-move-up">
            <a
                href="{{upperLink}}"
                class="action folder-icon btn-text"
                data-action="openCategory"
                data-id="{{categoryData.upperId}}"
                title="{{translate 'Up'}}"
            ><span class="fas fa-arrow-up text-soft transform-flip-x"></span></a>
        </div>
        {{/if}}
    </div>

    {{#if showFolders}}
        <div class="grid-auto-fill-xs">
            {{#each list}}
                <div class="category-cell">
                    <div class="category-item" data-id="{{id}}">
                        <a
                            href="{{link}}"
                            class="action link-gray"
                            data-action="openCategory"
                            data-id="{{id}}"
                            data-name="{{name}}"
                            title="{{name}}"
                        ><span class="folder-icon far fa-folder text-soft"></span> <span class="category-item-name">{{name}}</span></a>
                    </div>
                </div>
            {{/each}}

            {{#if showMoreIsActive}}
                <div class="category-cell">
                    <div class="category-item show-more">
                <span class="category-item-name">
                    <a
                        role="button"
                        tabindex="0"
                        class="action"
                        data-action="showMore"
                        title="{{translate 'Show more'}}"
                    >...</a>
                </span>
                    </div>
                </div>
            {{/if}}
        </div>
    {{/if}}

</div>
{{/unless}}

_delimiter_6nomlrqbt5x
res/templates/record/list-expanded.tpl
{{#if collection.models.length}}
    {{#if hasStickyBar}}
        <div class="list-sticky-bar sticked-bar hidden">
            {{#if hasPagination}}
                {{{paginationSticky}}}
            {{/if}}
        </div>
    {{/if}}

    {{#if topBar}}
        <div class="list-buttons-container clearfix">
            {{#if checkboxes}}{{#if massActionDataList}}
                <div class="btn-group actions">
                    <button
                        type="button"
                        class="btn btn-default dropdown-toggle actions-button"
                        data-toggle="dropdown"
                        disabled
                    >{{translate 'Actions'}} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        {{#each massActionDataList}}
                            <li {{#if hidden}}class="hidden"{{/if}}>
                                <a
                                    role="button"
                                    tabindex="0"
                                    data-action="{{name}}"
                                    class="mass-action"
                                >{{translate name category="massActions" scope=../scope}}</a>
                            </li>
                        {{/each}}
                    </ul>
                </div>
            {{/if}}{{/if}}

            {{#each buttonList}}
                {{button
                    name
                    scope=../scope
                    label=label
                    style=style
                    class='list-action-item'
                }}
            {{/each}}

            {{#if hasPagination}}
                {{{pagination}}}
            {{/if}}
        </div>
    {{/if}}

    <div class="list list-expanded">
        <ul class="list-group">
        {{#each rowDataList}}
            <li
                data-id="{{id}}"
                class="list-group-item list-row {{#if isStarred}} starred {{~/if}}"
            >{{{var id ../this}}}</li>
        {{/each}}
        </ul>

        {{#if showMoreEnabled}}
        {{#if showMoreActive}}
        <div class="show-more{{#unless showMoreActive}} hidden{{/unless}}">
            <a
                type="button"
                role="button"
                tabindex="0"
                class="btn btn-default btn-block"
                data-action="showMore"
                {{#if showCount}}title="{{translate 'Total'}}: {{totalCountFormatted}}"{{/if}}
            >
                {{#if showCount}}
                <div class="pull-right text-muted more-count">{{moreCountFormatted}}</div>
                {{/if}}
                <span>{{translate 'Show more'}}</span>
            </a>
        </div>
        {{/if}}
        {{/if}}
    </div>
{{else}}
    {{#unless noDataDisabled}}
        <div class="no-data">{{translate 'No Data'}}</div>
    {{/unless}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/record/list-checkbox.tpl
<span class="record-checkbox-container"><input
    type="checkbox"
    class="record-checkbox form-checkbox form-checkbox-small"
    data-id="{{model.id}}"
></span>

_delimiter_6nomlrqbt5x
res/templates/record/kanban.tpl

{{#if topBar}}
<div class="list-buttons-container clearfix">
    {{#if displayTotalCount}}
        <div class="text-muted total-count">
            <span
                title="{{translate 'Total'}}"
                class="total-count-span"
            >{{totalCountFormatted}}</span>
        </div>
    {{/if}}

    {{#if settings}}
        <div class="settings-container pull-right">{{{settings}}}</div>
    {{/if}}

    {{#each buttonList}}
        {{button
            name
            scope=../scope
            label=label
            style=style
            class='list-action-item'
        }}
    {{/each}}
</div>
{{/if}}

<div class="list-kanban-container">
<div class="list-kanban" data-scope="{{scope}}" style="min-width: {{minTableWidthPx}}px">
    <div class="kanban-head-container">
    <table class="kanban-head">
        <thead>
            <tr class="kanban-row">
                {{#each groupDataList}}
                    <th
                        data-name="{{name}}"
                        class="group-header{{#if style}} group-header-{{style}}{{/if}}"
                    >
                        <div>
                            <span class="kanban-group-label">{{label}}</span>
                            <a
                                role="button"
                                tabindex="0"
                                title="{{translate 'Create'}}"
                                class="create-button hidden"
                                data-action="createInGroup"
                                data-group="{{name}}"
                            >
                                <span class="fas fa-plus fa-sm"></span>
                            </a>
                        </div>
                    </th>
                {{/each}}
            </tr>
        </thead>
    </table>
    </div>
    <div class="kanban-columns-container">
    <table class="kanban-columns">
        {{#unless isEmptyList}}
        <tbody>
            <tr class="kanban-row">
                {{#each groupDataList}}
                <td class="group-column" data-name="{{name}}">
                    <div>
                        <div class="group-column-list" data-name="{{name}}">
                            {{#each dataList}}
                            <div class="item" data-id="{{id}}">{{{var key ../../this}}}</div>
                            {{/each}}
                        </div>
                        <div class="show-more">
                            <a data-action="groupShowMore" data-name="{{name}}" title="{{translate 'Show more'}}" class="{{#unless hasShowMore}}hidden {{/unless}}btn btn-link btn-sm"><span class="fas fa-ellipsis-h fa-sm"></span></a>
                        </div>
                    </div>
                </td>
                {{/each}}
            </tr>
        </tbody>
        {{/unless}}
    </table>
    </div>
</div>
</div>


{{#if isEmptyList}}{{#unless noDataDisabled}}
    <div class="margin-top no-data">
        {{translate 'No Data'}}
    </div>
{{/unless}}{{/if}}

_delimiter_6nomlrqbt5x
res/templates/record/kanban-item.tpl
<div class="panel panel-default {{#if isStarred}} starred {{~/if}} ">
    <div class="panel-body">
        {{#each layoutDataList}}
        <div>
            {{#if isFirst}}
            {{#unless rowActionsDisabled}}
            <div class="pull-right item-menu-container fix-position">{{{../itemMenu}}}</div>
            {{/unless}}
            {{/if}}
            <div class="form-group">
                <div
                    class="field{{#if isAlignRight}} field-right-align{{/if}}{{#if isLarge}} field-large{{/if}}{{#if isMuted}} text-muted{{/if}}"
                    data-name="{{name}}"
                >{{{var key ../this}}}</div>
            </div>
        </div>
        {{/each}}
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/record/edit.tpl
<div class="edit" id="{{id}}" data-scope="{{scope}}" tabindex="-1">
    {{#unless buttonsDisabled}}
    <div class="detail-button-container button-container record-buttons">
        <div class="sub-container clearfix">
            <div class="btn-group actions-btn-group" role="group">
                {{#each buttonList}}
                    {{button
                        name
                        scope=../entityType
                        label=label
                        labelTranslation=labelTranslation
                        style=style
                        html=html
                        hidden=hidden
                        title=title
                        text=text
                        className='btn-xs-wide detail-action-item'
                        disabled=disabled
                    }}
                {{/each}}
                {{#if dropdownItemList}}
                    <button
                        type="button"
                        class="btn btn-default dropdown-toggle{{#if dropdownItemListEmpty}} hidden{{/if}}"
                        data-toggle="dropdown"
                    ><span class="fas fa-ellipsis-h"></span>
                    </button>
                    <ul class="dropdown-menu pull-left">
                        {{#each dropdownItemList}}
                            {{#if this}}
                                {{dropdownItem
                                    name
                                    scope=../entityType
                                    label=label
                                    labelTranslation=labelTranslation
                                    html=html
                                    title=title
                                    text=text
                                    hidden=hidden
                                    disabled=disabled
                                    data=data
                                    className='detail-action-item'
                                }}
                            {{else}}
                                {{#unless @first}}
                                    {{#unless @last}}
                                        <li class="divider"></li>
                                    {{/unless}}
                                {{/unless}}
                            {{/if}}
                        {{/each}}
                    </ul>
                {{/if}}
            </div>
        </div>
    </div>
    {{/unless}}

    <div class="record-grid{{#if isWide}} record-grid-wide{{/if}}{{#if isSmall}} record-grid-small{{/if}}">
        <div class="left">
            {{#if hasMiddleTabs}}
            <div class="tabs middle-tabs btn-group">
                {{#each middleTabDataList}}
                <button
                    class="btn btn-text btn-wide{{#if isActive}} active{{/if}}{{#if hidden}} hidden{{/if}}"
                    data-tab="{{@key}}"
                >{{label}}</button>
                {{/each}}
            </div>
            {{/if}}
            <div class="middle">{{{middle}}}</div>
            <div class="extra">{{{extra}}}</div>
            <div class="bottom">{{{bottom}}}</div>
        </div>
        <div class="side{{#if hasMiddleTabs}} tabs-margin{{/if}}">
        {{{side}}}
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/record/detail.tpl
<div class="detail" id="{{id}}" data-scope="{{scope}}" tabindex="-1">
    {{#unless buttonsDisabled}}
    <div class="detail-button-container button-container record-buttons">
        <div class="sub-container clearfix">
            <div class="btn-group actions-btn-group" role="group">
                {{#each buttonList}}
                    {{button name
                             scope=../entityType
                             label=label
                             labelTranslation=labelTranslation
                             style=style
                             hidden=hidden
                             html=html
                             title=title
                             text=text
                             className='btn-xs-wide detail-action-item'
                             disabled=disabled
                    }}
                {{/each}}
                {{#if dropdownItemList}}
                    <button
                        type="button"
                        class="btn btn-default dropdown-toggle dropdown-item-list-button{{#if dropdownItemListEmpty}} hidden{{/if}}"
                        data-toggle="dropdown"
                    ><span class="fas fa-ellipsis-h"></span></button>
                    <ul class="dropdown-menu pull-left">
                        {{#each dropdownItemList}}
                            {{#if this}}
                                {{dropdownItem
                                    name
                                    scope=../entityType
                                    label=label
                                    labelTranslation=labelTranslation
                                    html=html
                                    title=title
                                    text=text
                                    hidden=hidden
                                    disabled=disabled
                                    data=data
                                    className='detail-action-item'
                                }}
                            {{else}}
                                {{#unless @first}}
                                    {{#unless @last}}
                                        <li class="divider"></li>
                                    {{/unless}}
                                {{/unless}}
                            {{/if}}
                        {{/each}}
                    </ul>
                {{/if}}
            </div>
            {{#if navigateButtonsEnabled}}
                <div class="pull-right">
                    <div class="btn-group" role="group">
                        <button
                            type="button"
                            class="btn btn-text btn-icon action {{#unless previousButtonEnabled}} disabled{{/unless}}"
                            data-action="previous"
                            title="{{translate 'Previous Entry'}}"
                            {{#unless previousButtonEnabled}}disabled="disabled"{{/unless}}
                        >
                            <span class="fas fa-chevron-left"></span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-text btn-icon action {{#unless nextButtonEnabled}} disabled{{/unless}}"
                            data-action="next"
                            title="{{translate 'Next Entry'}}"
                            {{#unless nextButtonEnabled}}disabled="disabled"{{/unless}}
                        >
                            <span class="fas fa-chevron-right"></span>
                        </button>
                    </div>
                </div>
            {{/if}}
        </div>
    </div>
    <div class="detail-button-container button-container edit-buttons hidden">
        <div class="sub-container clearfix">
            <div class="btn-group actions-btn-group" role="group">
                {{#each buttonEditList}}
                    {{button name
                             scope=../entityType
                             label=label
                             labelTranslation=labelTranslation
                             style=style
                             hidden=hidden
                             html=html
                             title=title
                             text=text
                             className='btn-xs-wide edit-action-item'
                             disabled=disabled
                    }}
                {{/each}}
                {{#if dropdownEditItemList}}
                    <button
                        type="button"
                        class="btn btn-default dropdown-toggle dropdown-edit-item-list-button{{#if dropdownEditItemListEmpty}} hidden{{/if}}"
                        data-toggle="dropdown"
                    ><span class="fas fa-ellipsis-h"></span></button>
                    <ul class="dropdown-menu pull-left">
                        {{#each dropdownEditItemList}}
                            {{#if this}}
                                {{dropdownItem
                                    name
                                    scope=../entityType
                                    label=label
                                    labelTranslation=labelTranslation
                                    html=html
                                    title=title
                                    text=text
                                    hidden=hidden
                                    disabled=disabled
                                    data=data
                                    className='edit-action-item'
                                }}
                            {{else}}
                                {{#unless @first}}
                                    {{#unless @last}}
                                        <li class="divider"></li>
                                    {{/unless}}
                                {{/unless}}
                            {{/if}}
                        {{/each}}
                    </ul>
                {{/if}}
            </div>
        </div>
    </div>
    {{/unless}}

    <div class="record-grid{{#if isWide}} record-grid-wide{{/if}}{{#if isSmall}} record-grid-small{{/if}}">
        <div class="left">
            {{#if hasMiddleTabs}}
            <div class="tabs middle-tabs btn-group">
                {{#each middleTabDataList}}
                <button
                    class="btn btn-text btn-wide{{#if isActive}} active{{/if}}{{#if hidden}} hidden{{/if}}"
                    data-tab="{{@key}}"
                >{{label}}</button>
                {{/each}}
            </div>
            {{/if}}
            <div class="middle">{{{middle}}}</div>
            <div class="extra">{{{extra}}}</div>
            <div class="bottom">{{{bottom}}}</div>
        </div>
        <div class="side{{#if hasMiddleTabs}} tabs-margin{{/if}}">
        {{{side}}}
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/record/bottom.tpl
{{#each panelList}}
    {{#if isRightAfterDelimiter}}
        <div class="panels-show-more-delimiter">
            <a role="button" tabindex="0" data-action="showMorePanels" title="{{translate 'Show more'}}">
                <span class="fas fa-ellipsis-h fa-lg"></span>
            </a>
        </div>
    {{/if}}
    {{#if isTabsBeginning}}
    <div class="tabs btn-group">
        {{#each ../tabDataList}}
        <button
            class="btn btn-text btn-wide{{#if isActive}} active{{/if}}{{#if hidden}} hidden{{/if}}"
            data-tab="{{@key}}"
        >{{label}}</button>
        {{/each}}
    </div>
    {{/if}}
    <div
        class="panel panel-{{#if style}}{{style}}{{else}}default{{/if}} panel-{{name}} headered{{#if hidden}} hidden{{/if}}{{#if sticked}} sticked{{/if}}{{#if tabHidden}} tab-hidden{{/if}}"
        data-name="{{name}}"
        data-style="{{#if style}}{{style}}{{/if}}"
        data-tab="{{tabNumber}}"
    >
        <div class="panel-heading">
            <div class="pull-right btn-group panel-actions-container">{{{var actionsViewKey ../this}}}</div>

            <h4 class="panel-title">
            {{#unless notRefreshable}}
            <span
                style="cursor: pointer; user-select: none;"
                class="action"
                title="{{translate 'clickToRefresh' category='messages'}}"
                data-action="refresh"
                data-panel="{{name}}"
            >
            {{/unless}}
            {{#if titleHtml}}
                {{{titleHtml}}}
            {{else}}
                {{title}}
            {{/if}}
            {{#unless notRefreshable}}
            </span>
            {{/unless}}
            </h4>
        </div>

        <div class="panel-body{{#if isForm}} panel-body-form{{/if}}" data-name="{{name}}">
            {{{var name ../this}}}
        </div>
    </div>
{{/each}}

_delimiter_6nomlrqbt5x
res/templates/record/row-actions/default.tpl
{{#if actionList.length}}
<div class="list-row-buttons btn-group pull-right">
    <button
        type="button"
        class="btn btn-link btn-sm dropdown-toggle"
        data-toggle="dropdown"
    ><span class="caret"></span></button>
    <ul class="dropdown-menu pull-right list-row-dropdown-menu" data-id="{{model.id}}">
    {{#each actionList}}
        {{#if this}}
            <li
                {{#if viewKey}} data-view-key="{{viewKey}}" {{/if}}
            >
                {{#if viewKey}}
                    {{{lookup ../this viewKey}}}
                {{else}}
                    <a
                        {{#if link}} href="{{link}}" {{else}} role="button" {{/if}}
                            tabindex="0"
                            class="action"
                            {{#if action}}data-action="{{action}}"{{/if}}
                        {{#each data}}
                            data-{{hyphen @key}}="{{./this}}"
                        {{/each}}
                    >{{#if html}}{{{html}}}{{else}}{{#if text}}{{text}}{{else}}{{translate label scope=../scope}}{{/if}}{{/if}}
                    </a>
                {{/if}}
            </li>
        {{else}}
            {{#unless @first}}
                {{#unless @last}}
                    <li class="divider"></li>
                {{/unless}}
            {{/unless}}
        {{/if}}
    {{/each}}
    </ul>
</div>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/record/panels/side.tpl
{{#if fieldList.length}}
<div class="row">
    {{#each fieldList}}
    <div class="cell form-group col-sm-6 col-md-12{{#if hidden}} hidden-cell{{/if}}" data-name="{{name}}">
        {{#unless noLabel}}
        <label
            class="control-label{{#if hidden}} hidden{{/if}}"
            data-name="{{name}}"
        >
            <span
                class="label-text"
            >{{#if labelText}}{{labelText}}{{else}}{{translate label scope=../model.entityType category='fields'}}{{/if}}</span>
        </label>
        {{/unless}}
        <div class="field{{#if hidden}} hidden{{/if}}" data-name="{{name}}">
        {{{var viewKey ../this}}}
        </div>
    </div>
    {{/each}}
</div>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/record/panels/relationship.tpl
<div class="list-container">{{{list}}}</div>

_delimiter_6nomlrqbt5x
res/templates/preferences/fields/smtp-email-address/detail.tpl
{{#if value}}
    {{value}}
{{else}}
    <span class="text-danger">{{translate 'userHasNoEmailAddress' category='messages' scope='Admin'}}</span>
    {{#if isAdmin}}
        <a href="#User/edit/{{prop model 'id'}}">{{translate 'Edit'}}</a>
    {{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/personal-data/record/record.tpl
{{#if fieldDataList.length}}
<div class="panel">
    <table class="table table-bordered-inside">
        {{#if editAccess}}
            <tr>
                <th style="width: 20px"><input type="checkbox" class="checkbox-all form-checkbox"></th>
                <th style="width: 30%"></th>
                <th></th>
            </tr>
        {{/if}}
        {{#each fieldDataList}}
            <tr>
                {{#if ../editAccess}}<td>{{#if editAccess}}
                    <input type="checkbox" class="checkbox form-checkbox" data-name="{{name}}">{{/if}}</td>{{/if}}
                <td style="width: 30%">{{translate name category='fields' scope=../scope}}</td>
                <td>
                    <div class="field" data-name="{{name}}">{{{var key ../this}}}</div>
                </td>
            </tr>
        {{/each}}
    </table>
</div>
{{else}}
{{translate 'No Data'}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/personal-data/modals/personal-data.tpl
<div class="record no-side-margin">{{{record}}}</div>

_delimiter_6nomlrqbt5x
res/templates/notification/panel.tpl
<div class="panel panel-default no-focus-outline" tabindex="-1">
    <div class="panel-heading panel-heading-no-title">
        <div class="link-group">
            <a href="#Notification" data-action="openNotifications">{{translate 'View List'}}</a>
            <a role="button" tabindex="0" data-action="markAllNotificationsRead">{{translate 'Mark all read'}}</a>
            <a role="button" tabindex="0" class="close-link" data-action="closePanel"><span class="fas fa-times"></span></a>
        </div>
        {{translate 'Notifications'}}
    </div>
    <div class="panel-body">
        <div class="list-container">
            <span class="text-soft fas fa-spinner fa-spin"></span>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/notification/list.tpl
<div class="page-header">
    <div class="row">
        <div class="col-sm-7">
            <h3>{{translate 'Notifications'}}</h3>
        </div>
        <div class="col-sm-5">
            <div class="pull-right btn-group">
                <button
                    class="btn btn-text"
                    data-action="markAllNotificationsRead"
                    title="{{translate 'Mark all read'}}"
                >{{translate 'Mark all read'}}</button>
                <button
                    class="btn btn-text btn-xs-wide btn-icon"
                    data-action="refresh"
                    title="{{translate 'checkForNewNotifications'
                    category='messages'}}"
                ><span class="fas fa-sync"></span>&nbsp;</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="list-container notification-list list-container-panel">{{{list}}}</div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/notification/badge.tpl
<a role="button" tabindex="0" class="notifications-button" data-action="showNotifications">
    <span class="fas fa-bell icon bell"></span>
    <span class="badge number-badge hidden"></span>
</a>
<div class="notifications-panel-container"></div>

_delimiter_6nomlrqbt5x
res/templates/notification/items/system.tpl
<div class="stream-head-container">
	<div class="stream-head-text-container text-danger">
		{{complexText message}}
	</div>
</div>
<div class="stream-date-container">
    <span class="text-muted small">{{{createdAt}}}</span>
</div>

_delimiter_6nomlrqbt5x
res/templates/notification/items/message.tpl
<div class="stream-head-container">
    <div class="pull-left">
        {{{avatar}}}
    </div>
    <div class="stream-head-text-container">
        <span class="{{style}} message">
            {{{message}}}
        </span>
    </div>
</div>

<div class="stream-date-container">
    <span class="text-muted small">{{{createdAt}}}</span>
</div>

_delimiter_6nomlrqbt5x
res/templates/notification/items/entity-removed.tpl
<div class="stream-head-container">
    <div class="pull-left">
        {{{avatar}}}
    </div>
    <div class="stream-head-text-container">
        <span class="text-muted message">{{{message}}}</span>
    </div>
</div>

<div class="stream-date-container">
    <span class="text-muted small">{{{createdAt}}}</span>
</div>

_delimiter_6nomlrqbt5x
res/templates/notification/items/email-received.tpl
<div class="stream-head-container">
    <div class="pull-left">
        {{{avatar}}}
    </div>
    <div class="stream-head-text-container">
        <span
            class="fas fa-envelope text-muted action icon"
            style="cursor: pointer;"
            title="{{translate 'View'}}"
            data-action="quickView"
            data-id="{{emailId}}"
            data-scope="Email"
        ></span><span class="message text-muted">{{{message}}}</span>
    </div>
</div>

<div class="stream-subject-container">
    <span class="cell cell-name"><a
        href="#Email/view/{{emailId}}"
        data-id="{{emailId}}"
        data-scope="Email"
    >{{emailName}}</a></span>
</div>

<div class="stream-date-container">
    <span class="text-muted small">{{{createdAt}}}</span>
</div>

_delimiter_6nomlrqbt5x
res/templates/notification/items/assign.tpl
<div class="stream-head-container">
    <div class="pull-left">
        {{{avatar}}}
    </div>
	<div class="stream-head-text-container text-muted message">
		{{{message}}}
	</div>
</div>
<div class="stream-date-container">
    <span class="text-muted small">{{{createdAt}}}</span>
</div>

_delimiter_6nomlrqbt5x
res/templates/notification/fields/read.tpl
{{#unless isRead}}
    <span class="badge-circle badge-circle-warning"> </span>
{{/unless}}

_delimiter_6nomlrqbt5x
res/templates/notification/fields/read-with-menu.tpl
    <div class="list-row-buttons pull-right">
        <div class="btn-group">
        <button type="button" class="btn btn-link btn-sm dropdown-toggle" data-toggle="dropdown">
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu pull-right">
            <li><a
                role="button"
                tabindex="0"
                class="action"
                data-action="quickRemove"
                data-id="{{model.id}}"
            >{{translate 'Remove'}}</a></li>
        </ul>
        </div>
    </div>
{{#unless isRead}}
    <span class="badge-circle badge-circle-warning"></span>
{{/unless}}

_delimiter_6nomlrqbt5x
res/templates/notification/fields/container.tpl
<div class="notification-container">{{{notification}}}</div>
{{#if hasGrouped}}
    <div class="notification-grouped">
    {{#if isGroupExpanded}}
        {{{groupedList}}}
    {{else}}
        <a
            role="button"
            data-action="showGrouped"
            class="btn btn-sm btn-text"
        ><span class="fas fa-ellipsis-h fa-sm"></span></a>
    {{/if}}
    </div>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/modals/select-records.tpl
<div class="search-container margin-bottom">{{{search}}}</div>
<div class="list-container">{{{list}}}</div>

_delimiter_6nomlrqbt5x
res/templates/modals/select-records-with-categories.tpl
<div class="search-container margin-bottom">{{{search}}}</div>

{{#unless categoriesDisabled}}
<div class="row row-list-container">
    <div class="categories-container col-md-3 col-sm-4">{{{categories}}}</div>
    <div class="list-container col-md-9 col-sm-8">{{{list}}}</div>
</div>
{{else}}
<div class="list-container">{{{list}}}</div>
{{/unless}}

_delimiter_6nomlrqbt5x
res/templates/modals/save-filters.tpl
<div class="panel panel-default no-side-margin">
<div class="panel-body">

<div class="cell form-group" data-name="name">
    <label class="control-label" data-name="name">{{translate 'name' category='fields'}}</label>
    <div class="field" data-name="name">
        {{{name}}}
    </div>
</div>

</div>
</div>

_delimiter_6nomlrqbt5x
res/templates/modals/related-list.tpl
<div class="search-container">{{{search}}}</div>
<div class="list-container">{{{list}}}</div>

_delimiter_6nomlrqbt5x
res/templates/modals/password-change-request.tpl
<div class="panel panel-default no-side-margin">
<div class="panel-body">

<div class="cell form-group" data-name="userName">
    <label class="control-label" data-name="userName">{{translate 'Username' scope='User'}}</label>
    <div class="field" data-name="userName">
        <input
            type="text"
            name="username"
            class="form-control"
            autocomplete="username"
            autocapitalize="off"
            spellcheck="false"
            maxlength="255"
        >
    </div>
</div>
<div class="cell form-group" data-name="emailAddress">
    <label class="control-label" data-name="emailAddress">{{translate 'Email Address' scope='User'}}</label>
    <div class="field" data-name="emailAddress">
        <input
            type="text"
            name="emailAddress"
            class="form-control"
            autocomplete="espo-change-password-emailAddress"
            autocapitalize="off"
            spellcheck="false"
            maxlength="255"
        >
    </div>
</div>

<div class="msg-box hidden"></div>

</div>
</div>

_delimiter_6nomlrqbt5x
res/templates/modals/mass-update.tpl
<div class="panel panel-default no-side-margin">
<div class="panel-body{{#if fieldList}} panel-body-form{{/if}}">

{{#unless fieldList}}
    <div>{{translate 'emptyMassUpdate' category='messages'}}</div>
{{else}}

<div class="button-container">
    <button class="btn btn-default pull-right hidden" data-action="reset">{{translate 'Reset'}}</button>
    <div class="btn-group">
        <button
            class="btn btn-default dropdown-toggle select-field"
            data-toggle="dropdown"
            tabindex="-1"
        >{{translate 'Add Field'}} <span class="caret"></span></button>
        <ul class="dropdown-menu pull-left filter-list">
        {{#each fieldList}}
            <li
                data-name="{{./this}}"
            ><a
                role="button"
                tabindex="0"
                data-name="{{./this}}"
                data-action="addField"
            >{{translate this scope=../entityType category='fields'}}</a></li>
        {{/each}}
        </ul>
    </div>
</div>

{{/unless}}
<div>
    <div class="fields-container"></div>
</div>

</div>
</div>

_delimiter_6nomlrqbt5x
res/templates/modals/mass-convert-currency.tpl
<div class="panel panel-default no-side-margin">
<div class="panel-body">

<div class="row">
    <div class="cell col-md-6 form-group" data-name="currency">
        <label class="control-label" data-name="currency">{{translate 'Convert to'}}</label>
        <div class="field" data-name="currency">{{{currency}}}</div>
    </div>
</div>
<div class="row">
    <div class="cell col-md-6 form-group" data-name="baseCurrency">
        <label class="control-label" data-name="baseCurrency">{{translate 'baseCurrency' category='fields' scope='Settings'}}</label>
        <div class="field" data-name="baseCurrency">{{{baseCurrency}}}</div>
    </div>
</div>
<div class="row">
    <div class="cell col-md-6 form-group" data-name="currencyRates">
        <label class="control-label" data-name="currencyRates">{{translate 'currencyRates' category='fields' scope='Settings'}}</label>
        <div class="field" data-name="currencyRates">{{{currencyRates}}}</div>
    </div>
</div>

</div>
</div>
_delimiter_6nomlrqbt5x
res/templates/modals/mass-action.tpl
<div class="record no-side-margin">{{{record}}}</div>

<div class="well info-text">{{complexText infoText}}</div>

_delimiter_6nomlrqbt5x
res/templates/modals/last-viewed.tpl
<div class="list-container">{{{list}}}</div>

_delimiter_6nomlrqbt5x
res/templates/modals/kanban-move-over.tpl
<ul class="list-group no-side-margin">
{{#each optionDataList}}
    <li class="list-group-item">
        <a role="button" tabindex="0" data-action="move" data-value="{{value}}">{{label}}</a>
    </li>
{{/each}}
</ul>

_delimiter_6nomlrqbt5x
res/templates/modals/image-preview.tpl
<div style="text-align: center;" class="image-container">
    <img src="{{url}}" style="max-width: 100%;">
</div>

_delimiter_6nomlrqbt5x
res/templates/modals/image-crop.tpl
<link href="{{basePath}}client/css/cropper.css" rel="stylesheet">
<div class="row">
    <div class="col-sm-6">
        <div style="text-align: center;" class="image-container"></div>
    </div>
    <div class="col-sm-6">
        <div class="btn-group">
            <button class="btn btn-default btn-icon" data-action="zoomIn"><span class="fas fa-search-plus"></span></button>
            <button class="btn btn-default btn-icon" data-action="zoomOut"><span class="fas fa-search-minus"></span></button>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/modals/edit.tpl
<div class="edit-container record no-side-margin">{{{edit}}}</div>

_delimiter_6nomlrqbt5x
res/templates/modals/edit-dashboard.tpl
<div class="no-side-margin record">
    <div>
        <div class="record-grid-wide">
            <div class="left">
                <div class="middle">
                    <div class="panel panel-default first last">
                        <div class="panel-body panel-body-form">
                            <div class="row">
                                <div class="cell form-group col-md-6" data-name="dashboardTabList">
                                    <label
                                        class="control-label"
                                        data-name="dashboardTabList"
                                    >{{translate 'dashboardTabList' category='fields' scope="Preferences"}}</label>
                                    <div class="field" data-name="dashboardTabList">
                                        {{{dashboardTabList}}}
                                    </div>
                                </div>
                                {{#if hasLocked}}
                                    <div class="cell form-group col-md-6" data-name="dashboardLocked">
                                        <label
                                            class="control-label"
                                            data-name="dashboardLocked"
                                        >{{translate 'dashboardLocked' category='fields' scope="Preferences"}}</label>
                                        <div class="field" data-name="dashboardLocked">
                                            {{{dashboardLocked}}}
                                        </div>
                                    </div>
                                {{/if}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


_delimiter_6nomlrqbt5x
res/templates/modals/duplicate.tpl
<h4>{{translate 'duplicate' category="messages"}}</h4>

{{#if scope}}
<div class="list-container margin-top-2x">{{{record}}}</div>
{{else}}
<div class="margin-top-2x">
    <table class="table table-panel">
        {{#each duplicates}}
        <tr>
            <td>
                <a
                    href="#{{#if _entityType}}{{_entityType}}{{else}}{{../scope}}{{/if}}/view/{{id}}"
                    target="_BLANK"
                >{{name}}</a>
                {{#if _entityType}}({{translate _entityType category='scopeNames'}}){{/if}}
            </td>
        </tr>
        {{/each}}
    </table>
    {{/if}}
</div>

_delimiter_6nomlrqbt5x
res/templates/modals/detail.tpl
<div class="record-container record no-side-margin">{{{record}}}</div>

_delimiter_6nomlrqbt5x
res/templates/modals/change-password.tpl
<div class="no-side-margin record">
    <div>
        <div class="record-grid-wide">
            <div class="left">
                <div class="middle">
                    <div class="panel panel-default first last">
                        <div class="panel-body panel-body-form">
                            <div class="row">
                                <div class="cell form-group col-md-6" data-name="currentPassword">
                                    <label
                                        class="control-label"
                                        data-name="currentPassword"
                                    >{{translate 'currentPassword' scope='User' category='fields'}}</label>
                                    <div class="field" data-name="currentPassword">{{{currentPassword}}}</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell form-group col-md-6" data-name="password">
                                    <label
                                        class="control-label"
                                        data-name="password"
                                    >{{translate 'newPassword' scope='User' category='fields'}}</label>
                                    <div class="field" data-name="password">{{{password}}}</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell form-group col-md-6" data-name="passwordConfirm">
                                    <label
                                        class="control-label"
                                        data-name="passwordConfirm"
                                    >{{translate 'passwordConfirm' scope='User' category='fields'}}</label>
                                    <div class="field" data-name="passwordConfirm">{{{passwordConfirm}}}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/modals/array-field-add.tpl
{{#unless optionDataList}}
    {{translate 'No Data'}}
{{/unless}}

{{#if optionDataList}}
    <div class="margin-bottom-2x margin-top">
        <input
            type="text"
            maxlength="64"
            placeholder="{{translate 'Search'}}"
            data-name="quick-search"
            class="form-control"
            spellcheck="false"
        >
    </div>
    <ul class="list-group list-group-panel array-add-list-group no-side-margin">
        {{#each optionDataList}}
            <li class="list-group-item clearfix" data-name="{{value}}">
                <input
                    class="cell form-checkbox form-checkbox-small"
                    type="checkbox"
                    data-value="{{value}}"
                >
                <a
                    role="button"
                    tabindex="0"
                    class="add text-bold"
                    data-value="{{value}}"
                >{{label}}</a>
            </li>
        {{/each}}
    </ul>
{{/if}}

<div class="no-data hidden">{{translate 'No Data'}}</div>

_delimiter_6nomlrqbt5x
res/templates/modals/add-dashlet.tpl
<div class="margin-bottom-2x margin-top">
    <input
        type="text"
        maxlength="64"
        placeholder="{{translate 'Search'}}"
        data-name="quick-search"
        class="form-control"
        spellcheck="false"
    >
</div>

<ul class="list-group list-group-panel array-add-list-group no-side-margin">
{{#each dashletList}}
    <li class="list-group-item" data-name="{{./this}}">
        <a
            role="button"
            tabindex="0"
            class="add text-bold"
            data-name="{{./this}}"
        >{{translate this category="dashlets"}}</a>
    </li>
{{/each}}
</ul>

<div class="no-data hidden">{{translate 'No Data'}}</div>

_delimiter_6nomlrqbt5x
res/templates/modals/action-history.tpl
<div class="search-container">{{{search}}}</div>
<div class="list-container">{{{list}}}</div>

_delimiter_6nomlrqbt5x
res/templates/lead-capture/opt-in-confirmation-success.tpl
<div class="container content">
    <div class="block-center-md">
        <div class="panel panel-success">
            <div class="panel-body">
                {{#if messageField}}
                <div class="field" data-name="message">
                    {{{messageField}}}
                </div>
                {{else}}
                <p>
                    {{defaultMessage}}
                </p>
                {{/if}}
            </div>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/lead-capture/opt-in-confirmation-expired.tpl
<div class="container content">
    <div class="block-center-md">
        <div class="panel panel-success">
            <div class="panel-body">
                <p>
                    {{defaultMessage}}
                </p>
            </div>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/import/step-2.tpl
<h4>{{translate 'Step 2' scope='Import'}}</h4>

    <div class="panel panel-default">
        <div class="panel-heading"><h4 class="panel-title">{{translate 'Field Mapping' scope='Import'}}</h4></div>
        <div class="panel-body">
            <div id="mapping-container">
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><h4 class="panel-title">{{translate 'Default Values' scope='Import'}}</h4></div>
        <div class="panel-body">
            <div class="button-container">
                <div class="btn-group">
                    <button class="btn btn-default dropdown-toggle add-field" data-toggle="dropdown">
                        {{translate 'Add Field' scope='Import'}}
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu pull-left default-field-list">
                        <li class="quick-search-list-item">
                            <input class="form-control add-field-quick-search-input">
                        </li>
                    {{#each fieldList}}
                        <li class="item" data-name="{{./this}}">
                            <a
                                role="button"
                                tabindex="0"
                                data-action="addField"
                                data-name="{{./this}}"
                            >{{translate this scope=../scope category='fields'}}</a></li>
                    {{/each}}
                    </ul>
                </div>
            </div>
            <div id="default-values-container" class="grid-auto-fill-md">
            </div>
        </div>
    </div>

    <div style="padding-bottom: 10px;" class="clearfix">
        <button
            class="btn btn-default btn-s-wide pull-left"
            data-action="back"
        >{{translate 'Back' scope='Import'}}</button>
        <button
            class="btn btn-danger btn-s-wide pull-right"
            data-action="next"
        >{{translate 'Run Import' scope='Import'}}</button>
    </div>

_delimiter_6nomlrqbt5x
res/templates/import/step-1.tpl
<h4>{{translate 'Step 1' scope='Import'}}</h4>

        <div class="panel panel-default">
            <div class="panel-heading"><h4 class="panel-title">{{translate 'What to Import?' scope='Import'}}</h4></div>
            <div class="panel-body panel-body-form">
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Entity Type' scope='Import'}}</label>
                        <div data-name="entityType" class="field">
                            {{{entityTypeField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'File (CSV)' scope='Import'}}</label>
                        <div>
                            <label class="attach-file-label">
                                <span class="btn btn-default btn-icon">
                                    <span class="fas fa-paperclip"></span>
                                </span>
                                <input type="file" id="import-file" accept=".csv" class="file">
                            </label>
                            <div class="import-file-name"></div>
                        </div>
                        <div class="text-muted import-file-info">{{translate 'utf8' category='messages' scope='Import'}}</div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'What to do?' scope='Import'}}</label>
                        <div data-name="action" class="field">
                            {{{actionField}}}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <div class="panel panel-default">
        <div class="panel-heading"><h4 class="panel-title">{{translate 'Parameters' scope='Import'}}</h4></div>
        <div class="panel-body panel-body-form">
            <div id="import-properties">
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Header Row' scope='Import'}}</label>
                        <div data-name="headerRow" class="field">
                            {{{headerRowField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Person Name Format' scope='Import'}}</label>
                        <div data-name="personNameFormat" class="field">
                            {{{personNameFormatField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <div class="pull-right">
                            <button
                                class="btn btn-link hidden"
                                data-action="saveAsDefault"
                            >{{translate 'saveAsDefault' category='strings' scope='Import'}}</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Field Delimiter' scope='Import'}}</label>
                        <div data-name="delimiter" class="field">
                            {{{delimiterField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Date Format' scope='Import'}}</label>
                        <div data-name="dateFormat" class="field">
                            {{{dateFormatField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Decimal Mark' scope='Import'}}</label>
                        <div data-name="decimalMark" class="field">
                            {{{decimalMarkField}}}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Text Qualifier' scope='Import'}}</label>
                        <div data-name="textQualifier" class="field">
                            {{{textQualifierField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Time Format' scope='Import'}}</label>
                        <div data-name="timeFormat" class="field">
                            {{{timeFormatField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Currency' scope='Import'}}</label>
                        <div data-name="currency" class="field">
                            {{{currencyField}}}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Timezone' scope='Import'}}</label>
                        <div data-name="timezone" class="field">
                            {{{timezoneField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'phoneNumberCountry' category='params' scope='Import'}}</label>
                        <div data-name="phoneNumberCountry" class="field">
                            {{{phoneNumberCountryField}}}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'inIdle' scope='Import' category='messages'}}</label>
                        <div data-name="idleMode" class="field">
                            {{{idleModeField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Skip searching for duplicates' scope='Import'}}</label>
                        <div data-name="skipDuplicateChecking" class="field">
                            {{{skipDuplicateCheckingField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Silent Mode' scope='Import'}}</label>
                        <div data-name="silentMode" class="field">
                            {{{silentModeField}}}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Run Manually' scope='Import' category='labels'}}</label>
                        <div data-name="manualMode" class="field">
                            {{{manualModeField}}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><h4 class="panel-title">{{translate 'Preview' scope='Import'}}</h4></div>
        <div class="panel-body">
            <div id="import-preview" style="overflow-x: auto; overflow-y: hidden;">
            {{translate 'No Data'}}
            </div>
        </div>
    </div>

    <div style="padding-bottom: 10px;" class="clearfix">
        {{#if entityList.length}}
        <button
            class="btn btn-primary btn-s-wide pull-right hidden"
            data-action="next"
        >{{translate 'Next' scope='Import'}}</button>
        {{/if}}
    </div>


_delimiter_6nomlrqbt5x
res/templates/import/index.tpl
<div class="page-header">
	<div class="row">
	    <div class="col-lg-7 col-sm-7">
	    	<h3>
	    	{{#if fromAdmin}}
    		<a href="#Admin">{{translate 'Administration' scope='Admin'}}</a>
    		<span class="breadcrumb-separator"><span></span></span>
		   	{{/if}}
		   	{{translate 'Import' category='scopeNames'}}
	   		</h3>
	    </div>
	    <div class="col-lg-5 col-sm-5">
	        <div class="header-buttons btn-group pull-right">
				<a href="#Import/list" class="btn btn-default">{{translate 'Import Results' scope='Import'}}</a>
	        </div>
	    </div>
	</div>
</div>

<div class="import-container">
    {{{step}}}
</div>


_delimiter_6nomlrqbt5x
res/templates/global-search/scope-badge.tpl
<span class="text-muted">{{{label}}}</span>

_delimiter_6nomlrqbt5x
res/templates/global-search/panel.tpl
<div class="panel panel-default">
    <div class="panel-heading panel-heading-no-title">
    <div class="link-group">
        <a role="button" tabindex="0" class="close-link" data-action="closePanel"><span class="fas fa-times"></span></a>
    </div>
    {{translate 'Global Search'}}
    </div>
    <div class="panel-body">
        <div class="list-container">
            <span class="text-soft fas fa-spinner fa-spin"></span>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/global-search/name-field.tpl
{{{iconHtml}}}<a href="#{{scope}}/view/{{id}}">{{name}}</a>

_delimiter_6nomlrqbt5x
res/templates/global-search/global-search.tpl
<div class="input-group has-feedback">
    <input
        type="search"
        class="form-control global-search-input"
        placeholder="{{translate 'Search'}}"
        autocomplete="espo-global-search"
        spellcheck="false"
    >
    {{#if hasSearchButton}}
    <div class="input-group-btn">
        <a
            class="btn btn-link global-search-button"
            data-action="search"
            title="{{translate 'Search'}}"
        ><span class="fas fa-search icon"></span></a>
    </div>
    {{/if}}
</div>
<div class="global-search-panel-container"></div>

_delimiter_6nomlrqbt5x
res/templates/fields/wysiwyg/edit.tpl
<textarea
	class="main-element form-control hidden auto-height"
	data-name="{{name}}"
	{{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}
	rows="{{rows}}"
	style="resize: none;"
></textarea>
<div class="summernote hidden"></div>

_delimiter_6nomlrqbt5x
res/templates/fields/wysiwyg/detail.tpl
{{#unless isPlain}}
    {{#if useIframe}}
    <div class="wysiwyg-iframe-container">
        <iframe frameborder="0" style="width: 100%; overflow-x: hidden; overflow-y: hidden;" class="hidden wysiwyg"></iframe>
    </div>
    {{else}}
    <div class="html-container">{{{value}}}</div>
    {{/if}}
{{else}}
<div class="plain complex-text hidden">{{complexText value}}</div>
{{/unless}}
{{#if isNone}}<span class="none-value">{{translate 'None'}}</span>{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/varchar/search.tpl
<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='varcharSearchRanges'}}
</select>
<input
    type="text"
    class="main-element form-control input-sm"
    data-name="{{name}}"
    value="{{searchData.value}}" {{#if params.maxLength}}
    maxlength="{{params.maxLength}}"{{/if}}{{#if params.size}}
    size="{{params.size}}"{{/if}}
    autocomplete="espo-{{name}}"
    placeholder="{{translate 'Value'}}"
    {{#if noSpellCheck}}
    spellcheck="false"
    {{/if}}
>
<div data-role="multi-select-container" class="hidden">
    <input data-role="multi-select-input">
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/varchar/list.tpl
{{#if value~}}
    <span
        title="{{value}}"
        {{#if textClass}}class="{{textClass}}"{{/if}}
    >{{value}}</span>
{{~/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/varchar/detail.tpl
{{#if isNotEmpty~}}
    {{~#if copyToClipboard~}}
        <a
            role="button"
            data-action="copyToClipboard"
            class="pull-right text-soft"
            title="{{translate 'Copy to Clipboard'}}"
        ><span class="far fa-copy"></span></a>
    {{~/if~}}
    <span class="{{#if textClass}}{{textClass}}{{/if}}">{{value}}</span>
{{~else}}
{{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
<span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/user-with-avatar/list.tpl
{{#if idValue}}
{{{avatar}}}<a href="#{{foreignScope}}/view/{{idValue}}" title="{{nameValue}}" class="text-default">{{nameValue}}</a>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/user-with-avatar/detail.tpl
{{#if idValue}}
{{{avatar}}}<a href="#{{foreignScope}}/view/{{idValue}}" class="text-default">{{nameValue}}</a>
{{else}}
    <span class="none-value">{{translate 'None'}}</span>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/user/search.tpl
<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='searchRanges'}}
</select>
<div class="primary">
	<div class="input-group">
	    <input
            class="form-control input-sm"
            type="text"
            data-name="{{nameName}}"
            value="{{searchData.nameValue}}"
            autocomplete="espo-{{name}}"
            placeholder="{{translate 'Select'}}"
            spellcheck="false"
        >
	    <span class="input-group-btn">
	        <button
                type="button"
                class="btn btn-sm btn-default btn-icon"
                data-action="selectLink"
                tabindex="-1"
                title="{{translate 'Select'}}"
            ><i class="fas fa-angle-up"></i></button>
	        <button
                type="button"
                class="btn btn-sm btn-default btn-icon"
                data-action="clearLink"
                tabindex="-1"
            ><i class="fas fa-times"></i></button>
	    </span>
	</div>
	<input type="hidden" data-name="{{idName}}" value="{{searchData.idValue}}">
</div>

<div class="one-of-container hidden">
    <div class="link-one-of-container link-container list-group"></div>

    <div class="input-group add-team">
        <input
            class="form-control input-sm element-one-of"
            type="text"
            value=""
            autocomplete="espo-{{name}}"
            placeholder="{{translate 'Select'}}"
            spellcheck="false"
        >
        <span class="input-group-btn">
            <button
                data-action="selectLinkOneOf"
                class="btn btn-default btn-sm btn-icon"
                type="button"
                tabindex="-1"
                title="{{translate 'Select'}}"
            ><span class="fas fa-angle-up"></span></button>
        </span>
    </div>
</div>

<div class="teams-container hidden">
    <div class="link-teams-container link-container list-group"></div>
    <div class="input-group add-team">
        <input
            class="form-control input-sm element-teams"
            type="text"
            value=""
            autocomplete="espo-{{name}}"
            placeholder="{{translate 'Select'}}"
            spellcheck="false"
        >
        <span class="input-group-btn">
            <button
                data-action="selectLinkTeams"
                class="btn btn-default btn-sm btn-icon"
                type="button"
                tabindex="-1"
                title="{{translate 'Select'}}"
            ><span class="fas fa-angle-up"></span></button>
        </span>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/url/list.tpl
{{#if value}}
	<a
        href="{{url}}"
        target="_blank"
        title="{{value}}"
        rel="noopener noreferrer"
        class="text-default"
    >{{value}}</a>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/url/detail.tpl
{{#if value~}}
    {{~#if copyToClipboard~}}
        <a
            role="button"
            data-action="copyToClipboard"
            class="pull-right text-soft"
            title="{{translate 'Copy to Clipboard'}}"
        ><span class="far fa-copy"></span></a>
    {{~/if~}}
	<a
        href="{{url}}"
        target="_blank"
        rel="noopener noreferrer"
    >{{value}}</a>
{{~else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
    <span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/text/search.tpl
<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='varcharSearchRanges'}}
</select>
<input
    type="text"
    class="main-element form-control input-sm"
    data-name="{{name}}"
    value="{{searchData.value}}"
    {{#if params.maxLength}}maxlength="{{params.maxLength}}"{{/if}}
    {{#if params.size}} size="{{params.size}}"{{/if}}
    autocomplete="espo-{{name}}"
    placeholder="{{translate 'Value'}}"
>

_delimiter_6nomlrqbt5x
res/templates/fields/text/list.tpl
{{#if isNotEmpty}}
<div
    class="complex-text-container{{#if isCut}} cut{{/if}}"
    {{#if cutHeight}} style="max-height: {{cutHeight}}px;"{{/if}}
><div
    class="complex-text"
>{{#unless displayRawText}}{{#if htmlValue}}{{{htmlValue}}}{{else}}{{complexText value}}{{/if}}{{else}}{{breaklines value}}{{/unless}}</div></div>
{{#if isCut}}<div
    class="see-more-container hidden"
><a
    role="button"
    tabindex="0"
    data-action="seeMoreText"
><span class="fas fa-sm fa-chevron-down"></span> <span class="text">{{translate 'See more'}}</span></a></div>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/text/edit.tpl
<textarea
	class="main-element form-control auto-height"
	data-name="{{name}}"
	{{#if params.maxLength}}maxlength="{{params.maxLength}}"{{/if}}
	rows="{{rows}}"
	autocomplete="espo-{{name}}"
	style="resize: {{#unless noResize}} vertical{{else}}none{{/unless}};"
>{{value}}</textarea>
{{#if preview}}
    <div>
        <a
            role="button"
            class="text-muted pull-right stream-post-preview{{#unless isNotEmpty}} hidden{{/unless}}"
            data-action="previewText"
            title="{{translate 'Preview'}}"
        ><span class="fas fa-eye"></span></a>
    </div>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/text/detail.tpl
{{#if isNotEmpty}}
<div
    class="complex-text-container{{#if isCut}} cut{{/if}}"
    {{#if cutHeight}} style="max-height: {{cutHeight}}px;"{{/if}}
>
    <div class="complex-text">{{#unless displayRawText}}{{#if htmlValue}}{{{htmlValue}}}{{else}}{{complexText value}}{{/if}}{{else}}{{breaklines value}}{{/unless}}</div>
</div>
{{#if isCut}}
<div class="see-more-container hidden">
    <a
        role="button"
        tabindex="0"
        data-action="seeMoreText"
    ><span class="fas fa-sm fa-chevron-down"></span> <span class="text">{{translate 'See more'}}</span></a>
</div>
{{/if}}
{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
    <span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/range-int/edit.tpl
<div class="row">
    <div class="col-sm-6 col-xs-6">
        <input type="text" class="form-control" data-name="from{{ucName}}" value="{{fromValue}}" placeholder="{{translate 'From' scope=scope}}">
    </div>
    <div class="col-sm-6 col-xs-6">
        <input type="text" class="form-control" data-name="to{{ucName}}" value="{{toValue}}" placeholder="{{translate 'To' scope=scope}}">
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/range-int/detail.tpl
{{{value}}}
_delimiter_6nomlrqbt5x
res/templates/fields/range-currency/edit.tpl
<div class="row">
    <div class="col-sm-6 col-xs-6">
        <input type="text" class="form-control" data-name="from{{ucName}}" value="{{fromValue}}" placeholder="{{translate 'From' scope=scope}}">
    </div>
    <div class="col-sm-6 col-xs-6">
        <input type="text" class="form-control" data-name="to{{ucName}}" value="{{toValue}}" placeholder="{{translate 'To' scope=scope}}">
    </div>
    <div class="col-sm-12 col-xs-12">
        <select data-name="{{currencyField}}" class="form-control">
            {{{options currencyList currencyValue}}}
        </select>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/phone/list.tpl
{{#if isErased}}
    {{value}}
{{else}}
{{#unless isInvalid}}
    <a
        href="tel:{{valueForLink}}"
        data-phone-number="{{valueForLink}}"
        data-action="dial"
        title="{{value}}"
        class="selectable text-default"
        {{#if isOptedOut}}style="text-decoration: line-through;"{{/if}}
    >{{value}}</a>
{{else}}
    <span title="{{value}}" style="text-decoration: line-through;">{{value}}</span>
{{/unless}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/phone/edit.tpl
<div class="phone-number-block-container">
{{#each phoneNumberData}}
    <div class="input-group phone-number-block {{#if ../onlyPrimary}} only-primary {{/if}}">
        {{#unless ../onlyPrimary}}
        <span class="input-group-item">
            <select
                data-property-type="type"
                class="form-control radius-left"
            >{{options ../params.typeList type scope=../scope field=../name}}</select>
        </span>
        {{/unless}}
        <span class="input-group-item input-group-item-middle input-phone-number-item">
            <input
                type="text"
                class="form-control phone-number numeric-text no-margin-shifting {{#if optOut}} text-strikethrough {{/if}} {{#if invalid}} text-danger {{/if}}"
                value="{{phoneNumber}}"
                autocomplete="espo-{{../name}}"
                maxlength={{../itemMaxLength}}
            >
        </span>
        {{#unless ../onlyPrimary}}
        <span class="input-group-btn">
            <button
                class="btn btn-default btn-icon phone-property{{#if primary}} active{{/if}} hidden"
                type="button"
                data-action="switchPhoneProperty"
                data-property-type="primary"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Primary' scope='PhoneNumber'}}"
            >
                <span class="fas fa-star fa-sm{{#unless primary}} text-muted{{/unless}}"></span>
            </button>
            <button
                class="btn btn-default btn-icon phone-property{{#if optOut}} active{{/if}}"
                type="button"
                data-action="switchPhoneProperty"
                data-property-type="optOut"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Opted Out' scope='EmailAddress'}}"
            >
                <span class="fas fa-ban{{#unless optOut}} text-muted{{/unless}}"></span>
            </button>
            <button
                class="btn btn-default btn-icon radius-right phone-property{{#if invalid}} active{{/if}}"
                type="button"
                data-action="switchPhoneProperty"
                data-property-type="invalid"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Invalid' scope='EmailAddress'}}"
            >
                <span class="fas fa-exclamation-circle{{#unless invalid}} text-muted{{/unless}}"></span>
            </button>
            <button
                class="btn btn-link btn-icon hidden"
                type="button"
                tabindex="-1"
                data-action="removePhoneNumber"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Remove'}}"
            >
                <span class="fas fa-times"></span>
            </button>
        </span>
        {{/unless}}
    </div>
{{/each}}
</div>

{{#unless onlyPrimary}}
    <button
        class="btn btn-default btn-icon"
        type="button"
        data-action="addPhoneNumber"
    ><span class="fas fa-plus"></span></button>
{{/unless}}

_delimiter_6nomlrqbt5x
res/templates/fields/phone/detail.tpl
{{#if phoneNumberData}}
    {{#each phoneNumberData}}
        <div>
            {{#unless invalid}}
            {{#unless erased}}
            <a
                href="tel:{{valueForLink}}"
                data-phone-number="{{valueForLink}}"
                data-action="dial"
                style="display: inline-block;"
                class="selectable"
            >
            {{/unless}}
            {{/unless}}
            <span {{#if lineThrough}}style="text-decoration: line-through"{{/if}}>{{phoneNumber}}</span>
            {{#unless invalid}}
            {{#unless erased}}
            </a>
            {{/unless}}
            {{/unless}}
            {{#if type}}
            <span class="text-muted small">{{translateOption type scope=../scope field=../name}}</span>
            {{/if}}
        </div>
    {{/each}}
{{else}}
    {{#if value}}
    {{#if lineThrough}}<s>{{/if}}<a
            href="tel:{{valueForLink}}"
            data-phone-number="{{valueForLink}}"
            data-action="dial"
            class="selectable"
        >{{value}}</a>{{#if lineThrough}}</s>{{/if}}
    {{else}}
        {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
        <span class="loading-value"></span>{{/if}}
    {{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/person-name/edit.tpl
<div class="row">
    <div class="col-sm-3 col-xs-3">
        <select data-name="salutation{{ucName}}" class="form-control">
            {{options salutationOptions salutationValue field=salutationField scope=scope}}
        </select>
    </div>
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="first{{ucName}}" value="{{firstValue}}" placeholder="{{translate 'First Name'}}"{{#if firstMaxLength}} maxlength="{{firstMaxLength}}"{{/if}} autocomplete="espo-first{{ucName}}">
    </div>
    <div class="col-sm-5 col-xs-5">
        <input type="text" class="form-control" data-name="last{{ucName}}" value="{{lastValue}}" placeholder="{{translate 'Last Name'}}"{{#if lastMaxLength}} maxlength="{{lastMaxLength}}"{{/if}} autocomplete="espo-last{{ucName}}">
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/person-name/edit-last-first.tpl
<div class="row">
    <div class="col-sm-3 col-xs-3">
        <select data-name="salutation{{ucName}}" class="form-control">
            {{options salutationOptions salutationValue field=salutationField scope=scope}}
        </select>
    </div>
    <div class="col-sm-5 col-xs-5">
        <input type="text" class="form-control" data-name="last{{ucName}}" value="{{lastValue}}" placeholder="{{translate 'Last Name'}}"{{#if lastMaxLength}} maxlength="{{lastMaxLength}}"{{/if}} autocomplete="espo-last{{ucName}}">
    </div>
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="first{{ucName}}" value="{{firstValue}}" placeholder="{{translate 'First Name'}}"{{#if firstMaxLength}} maxlength="{{firstMaxLength}}"{{/if}} autocomplete="espo-first{{ucName}}">
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/person-name/edit-last-first-middle.tpl
<div class="row">
    <div class="col-sm-3 col-xs-3">
        <select data-name="salutation{{ucName}}" class="form-control">
            {{options salutationOptions salutationValue field=salutationField scope=scope}}
        </select>
    </div>
    <div class="col-sm-9 col-xs-9">
        <input type="text" class="form-control" data-name="last{{ucName}}" value="{{lastValue}}" placeholder="{{translate 'Last Name'}}"{{#if lastMaxLength}} maxlength="{{lastMaxLength}}"{{/if}} autocomplete="espo-last{{ucName}}">
    </div>
    <div class="col-sm-6 col-xs-6">
        <input type="text" class="form-control" data-name="first{{ucName}}" value="{{firstValue}}" placeholder="{{translate 'First Name'}}"{{#if firstMaxLength}} maxlength="{{firstMaxLength}}"{{/if}} autocomplete="espo-first{{ucName}}">
    </div>
    <div class="col-sm-6 col-xs-6">
        <input type="text" class="form-control" data-name="middle{{ucName}}" value="{{middleValue}}" placeholder="{{translate 'Middle Name'}}"{{#if middleMaxLength}} maxlength="{{middleMaxLength}}"{{/if}} autocomplete="espo-middle{{ucName}}">
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/person-name/edit-first-middle-last.tpl
<div class="row">
    <div class="col-sm-3 col-xs-3">
        <select data-name="salutation{{ucName}}" class="form-control">
            {{options salutationOptions salutationValue field=salutationField scope=scope}}
        </select>
    </div>
    <div class="col-sm-5 col-xs-5">
        <input type="text" class="form-control" data-name="first{{ucName}}" value="{{firstValue}}" placeholder="{{translate 'First Name'}}"{{#if firstMaxLength}} maxlength="{{firstMaxLength}}"{{/if}} autocomplete="espo-first{{ucName}}">
    </div>
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="middle{{ucName}}" value="{{middleValue}}" placeholder="{{translate 'Middle Name'}}"{{#if middleMaxLength}} maxlength="{{middleMaxLength}}"{{/if}} autocomplete="espo-middle{{ucName}}">
    </div>
    <div class="col-sm-12 col-xs-12">
        <input type="text" class="form-control" data-name="last{{ucName}}" value="{{lastValue}}" placeholder="{{translate 'Last Name'}}"{{#if lastMaxLength}} maxlength="{{lastMaxLength}}"{{/if}} autocomplete="espo-last{{ucName}}">
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/person-name/detail.tpl
{{#if isNotEmpty}}{{formattedValue}}
{{else}}
{{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
<span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/password/edit.tpl
{{#unless isNew}}
<a role="button" tabindex="0" data-action="change">{{translate 'change'}}</a>
{{/unless}}
<input
	type="password"
	class="main-element form-control{{#unless isNew}} hidden{{/unless}}"
	data-name="{{name}}"
	value="{{value}}"
	autocomplete="new-password"
	{{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}
>

_delimiter_6nomlrqbt5x
res/templates/fields/password/detail.tpl
**********
_delimiter_6nomlrqbt5x
res/templates/fields/multi-enum/edit.tpl

<input data-name="{{name}}" type="text" class="{{#if viewObject.params.displayAsList}}as-list{{/if}}">

_delimiter_6nomlrqbt5x
res/templates/fields/map/detail.tpl
{{#if hasAddress}}
<div class="map"></div>
{{else}}
<span class="none-value">{{translate 'None'}}</span>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/link-parent/search.tpl
<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='searchRanges'}}
</select>
<div class="primary">
    <select class="form-control input-sm entity-type" data-name="{{typeName}}">
        {{options foreignScopeList searchData.typeValue category='scopeNames'}}
    </select>
    <div class="input-group">
        <input
            class="form-control input-sm"
            type="text"
            data-name="{{nameName}}"
            value="{{searchData.nameValue}}"
            autocomplete="espo-{{name}}"
            placeholder="{{translate 'Select'}}"
            spellcheck="false"
        >
        <span class="input-group-btn">
            <button
                type="button"
                class="btn btn-sm btn-default btn-icon"
                data-action="selectLink"
                title="{{translate 'Select'}}"><i class="fas fa-angle-up"></i></button>
            <button
                type="button"
                class="btn btn-sm btn-default btn-icon"
                data-action="clearLink"
            ><i class="fas fa-times"></i></button>
        </span>
    </div>
    <input type="hidden" data-name="{{idName}}" value="{{searchData.idValue}}">
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/link-parent/list.tpl
{{#if idValue~}}
	{{#if iconHtml}}{{{iconHtml}}}{{/if~}}
        <a
            href="#{{foreignScope}}/view/{{idValue}}"
            title="{{nameValue}}"
            class="text-default"
        >{{nameValue}}</a>
{{~/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/link-parent/list-link.tpl
<a href="#{{scope}}/view/{{model.id}}" class="link" data-id="{{model.id}}" title="{{value}}">
{{#if idValue}}
{{#if value}}{{value}}{{else}}{{translate 'None'}}{{/if}}
{{else}}
    {{translate 'None'}}
{{/if}}
</a>
_delimiter_6nomlrqbt5x
res/templates/fields/link-parent/edit.tpl
<div class="input-group input-group-link-parent">
    {{#if foreignScopeList.length}}
    <span class="input-group-item">
        <select class="form-control radius-left" data-name="{{typeName}}">
            {{options foreignScopeList foreignScope category='scopeNames'}}
        </select>
    </span>
    <span class="input-group-item input-group-item-middle">
        <input
            class="main-element form-control"
            type="text"
            data-name="{{nameName}}"
            value="{{nameValue}}"
            autocomplete="espo-{{name}}"
            placeholder="{{translate 'Select'}}"
            spellcheck="false"
        >
    </span>
    <span class="input-group-btn">
        <button
            data-action="selectLink"
            class="btn btn-default btn-icon"
            type="button"
            title="{{translate 'Select'}}"
        ><i class="fas fa-angle-up"></i></button>
        <button
            data-action="clearLink"
            class="btn btn-default btn-icon"
            type="button"
        ><i class="fas fa-times"></i></button>
    </span>
    {{else}}
    {{translate 'None'}}
    {{/if}}
</div>
<input type="hidden" data-name="{{idName}}" value="{{idValue}}">

_delimiter_6nomlrqbt5x
res/templates/fields/link-parent/detail.tpl
{{#if idValue}}{{#if iconHtml}}{{{iconHtml}}}{{/if}}<a href="#{{foreignScope}}/view/{{idValue}}" title="{{translate foreignScope category='scopeNames'}}">{{nameValue}}</a>
{{else}}
    {{#if valueIsSet}}
        {{#if displayEntityType}}{{translate typeValue category='scopeNames'}}
        {{else}}<span class="none-value">{{translate 'None'}}</span>
        {{/if}}
    {{else}}<span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/link-multiple/search.tpl
<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='searchRanges'}}
</select>

<div class="link-group-container hidden">

    <div class="link-container list-group"></div>

    <div class="input-group add-team">
        <input
            class="main-element form-control input-sm"
            type="text"
            value=""
            autocomplete="espo-{{name}}"
            placeholder="{{translate 'Select'}}"
            spellcheck="false"
        >
        <span class="input-group-btn">
            <button
                data-action="selectLink"
                class="btn btn-default btn-icon btn-sm"
                type="button"
                title="{{translate 'Select'}}"
            ><span class="fas fa-angle-up"></span></button>
        </span>
    </div>

    <input type="hidden" data-name="{{name}}Ids" value="{{searchParams.value}}" class="ids">
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/link-multiple/list.tpl
{{#if value}}
    {{{value}}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/link-multiple/edit.tpl
<div class="link-container list-group"></div>

<div class="input-group add-team">
    <input
        class="main-element form-control"
        type="text"
        value=""
        autocomplete="espo-{{name}}"
        placeholder="{{translate 'Select'}}"
        spellcheck="false"
    >
    <span class="input-group-btn">
        {{#if createButton}}
            <button
                data-action="createLink"
                class="btn btn-default btn-icon"
                type="button"
                title="{{translate 'Create'}}"
            ><i class="fas fa-plus"></i></button>
        {{/if}}
        <button
            data-action="selectLink"
            class="btn btn-default btn-icon"
            type="button"
            title="{{translate 'Select'}}"
        ><span class="fas fa-angle-up"></span></button>
    </span>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/link-multiple/detail.tpl
{{#if value}}
    {{{value}}}
{{else}}
    {{#if valueIsSet}}
        <span class="none-value">{{translate 'None'}}</span>
    {{else}}
        <span class="loading-value"></span>
    {{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/link/search.tpl
<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='searchRanges'}}
</select>
<div class="primary">
	<div class="input-group">
	    <input
            class="form-control input-sm"
            type="text"
            data-name="{{nameName}}"
            value="{{searchData.nameValue}}"
            autocomplete="espo-{{name}}"
            placeholder="{{translate 'Select'}}"
            spellcheck="false"
        >
	    <span class="input-group-btn">
	        <button
                type="button"
                class="btn btn-sm btn-default btn-icon"
                data-action="selectLink"
                title="{{translate 'Select'}}"
            ><i class="fas fa-angle-up"></i></button>
	        <button
                type="button"
                class="btn btn-sm btn-default btn-icon"
                data-action="clearLink"
            ><i class="fas fa-times"></i></button>
	    </span>
	</div>
	<input type="hidden" data-name="{{idName}}" value="{{searchData.idValue}}">
</div>

<div class="one-of-container hidden">
    <div class="link-one-of-container link-container list-group">
    </div>

    <div class="input-group add-team">
        <input
            class="form-control input-sm element-one-of"
            type="text"
            value=""
            autocomplete="espo-{{name}}"
            placeholder="{{translate 'Select'}}"
            spellcheck="false"
        >
        <span class="input-group-btn">
            <button
                data-action="selectLinkOneOf"
                class="btn btn-default btn-sm btn-icon"
                type="button" tabindex="-1"
                title="{{translate 'Select'}}"
            ><span class="fas fa-angle-up"></span></button>
        </span>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/link/list.tpl
{{#if url~}}
    {{~#if iconHtml}}{{{iconHtml}}}{{/if}}<a href="{{url}}" title="{{nameValue}}" class="text-default">{{nameValue}}</a>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/link/edit.tpl
<div class="input-group">
    <input
        class="main-element form-control"
        type="text"
        data-name="{{nameName}}"
        value="{{nameValue}}"
        autocomplete="espo-{{name}}"
        placeholder="{{translate 'Select'}}"
        spellcheck="false"
    >
    <span class="input-group-btn">
        {{#if createButton}}
        <button
            data-action="createLink"
            class="btn btn-default btn-icon{{#if idValue}} hidden{{/if}}"
            type="button"
            title="{{translate 'Create'}}"
        ><i class="fas fa-plus"></i></button>
        {{/if}}
        <button
            data-action="selectLink"
            class="btn btn-default btn-icon"
            type="button"
            title="{{translate 'Select'}}"
        ><i class="fas fa-angle-up"></i></button>
        <button
            data-action="clearLink"
            class="btn btn-default btn-icon"
            type="button"
        ><i class="fas fa-times"></i></button>
    </span>
</div>
<input type="hidden" data-name="{{idName}}" value="{{idValue}}">

_delimiter_6nomlrqbt5x
res/templates/fields/link/detail.tpl
{{#if url}}
{{#if iconHtml}}{{{iconHtml}}}{{/if}}<a href="{{url}}" class="{{#if linkClass}}{{linkClass}}{{/if}}">{{nameValue}}</a>
{{else}}
    {{#if valueIsSet}}
    <span class="none-value">{{translate 'None'}}</span>
    {{else}}
    <span class="loading-value"></span>
    {{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/json-object/detail.tpl
{{#if isNotEmpty}}
{{{value}}}
{{else}}
{{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
<span class="loading-value"></span>{{/if}}{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/int/search.tpl
<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='intSearchRanges'}}
</select>
<input
    type="text"
    class="form-control input-sm hidden numeric-text"
    data-name="{{name}}"
    value="{{value}}"
    pattern="[\-]?[0-9]*"
    {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}
    placeholder="{{translate 'Value'}}"
    autocomplete="espo-{{name}}"
    spellcheck="false"
>
<input
    type="text"
    class="form-control{{#ifNotEqual searchType 'between'}} hidden{{/ifNotEqual}} additional input-sm numeric-text"
    data-name="{{name}}-additional"
    value="{{value2}}"
    pattern="[\-]?[0-9]*"
    {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}
    placeholder="{{translate 'Value'}}"
    autocomplete="espo-{{name}}-additional"
    spellcheck="false"
>

_delimiter_6nomlrqbt5x
res/templates/fields/int/list.tpl
{{#if isNotEmpty}}<span title="{{value}}" class="numeric-text">{{value}}</span>{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/int/edit.tpl

<input type="text" class="main-element form-control numeric-text" data-name="{{name}}" value="{{value}}" autocomplete="espo-{{name}}" pattern="[\-]?[0-9]*" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}>

_delimiter_6nomlrqbt5x
res/templates/fields/int/detail.tpl
{{#if isNotEmpty}}<span class="numeric-text">{{value}}</span>{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>
    {{else}}<span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/formula/edit.tpl
<div class="formula-edit-container clearfix">
    <div>
        <div id="{{containerId}}">{{value}}</div>
    </div>
    {{#if hasSide}}
    <div>
        <div class="button-container">
            <div class="btn-group pull-right">
                {{#if hasCheckSyntax}}
                <button
                    type="button"
                    class="btn btn-text btn-sm btn-icon"
                    data-action="checkSyntax"
                    title="{{translate 'Check Syntax' scope='Formula'}}"
                ><span class="far fa-circle"></span></button>
                {{/if}}
                {{#if hasInsert}}
                <button
                    type="button"
                    class="btn btn-text btn-sm dropdown-toggle btn-icon"
                    data-toggle="dropdown"
                ><span class="fas fa-plus"></span></button>
                <ul class="dropdown-menu pull-right">
                    {{#if targetEntityType}}
                    <li><a role="button" tabindex="0" data-action="addAttribute">{{translate 'Attribute'}}</a></li>
                    {{/if}}
                    <li><a role="button" tabindex="0" data-action="addFunction">{{translate 'Function'}}</a></li>
                </ul>
                {{/if}}
            </div>
        </div>
    </div>
    {{/if}}
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/formula/detail.tpl
{{#if isNotEmpty}}
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div id="{{containerId}}">{{value}}</div>
    </div>
</div>
{{else}}<span class="none-value">{{translate 'None'}}</span>{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/float/edit.tpl

<input type="text" class="main-element form-control numeric-text" data-name="{{name}}" value="{{value}}" autocomplete="espo-{{name}}" pattern="[\-]?[0-9,.]*" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}>

_delimiter_6nomlrqbt5x
res/templates/fields/file/list.tpl
{{#if value}}{{{value}}}{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/file/edit.tpl
<div class="attachment-upload">
    <div class="attachment-button{{#if id}} hidden{{/if}} clearfix ">
        <div class="pull-left">
        <label class="attach-file-label" title="{{translate 'Attach File'}}" tabindex="0">
            <span class="btn btn-default btn-icon"><span class="fas fa-paperclip"></span></span>
            <input
                type="file"
                class="file pull-right"
                {{#if acceptAttribute}}accept="{{acceptAttribute}}"{{/if}}
                tabindex="-1"
            >
        </label>
        </div>
        {{#unless id}}
        {{#if sourceList.length}}
        <div class="pull-left dropdown">
            <button class="btn btn-default btn-icon dropdown-toggle" type="button" data-toggle="dropdown">
                <span class="fas fa-file"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
            {{#each sourceList}}
                <li><a
                        role="button"
                        tabindex="0"
                        class="action"
                        data-action="insertFromSource"
                        data-name="{{./this}}"
                    >{{translate this category='insertFromSourceLabels' scope='Attachment'}}</a></li>
            {{/each}}
            </ul>
        </div>
        {{/if}}
        {{/unless}}
    </div>

    <div class="attachment"></div>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/file/detail.tpl
{{#if value}}
    {{{value}}}
{{else}}
    {{#if valueIsSet}}
    <span class="none-value">{{translate 'None'}}</span>
    {{else}}<span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/enum/search.tpl
<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='searchRanges'}}
</select>
<div class="input-container"><input class="main-element" type="text"></div>

_delimiter_6nomlrqbt5x
res/templates/fields/enum/list.tpl
{{#if isNotEmpty}}
{{#if style}}
<span
    class="{{class}}-{{style}}"
    title="{{valueTranslated}}"
>{{/if}}{{valueTranslated}}{{#if style}}</span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/enum/list-link.tpl
<a href="#{{scope}}/view/{{model.id}}" class="link" data-id="{{model.id}}" title="{{value}}">
    {{#if value}}
        {{#if style}}
        <span
            class="{{class}}-{{style}}"
            title="{{valueTranslated}}"
        >{{/if}}{{valueTranslated}}{{#if style}}</span>{{/if}}
    {{else}}
        {{translate 'None'}}
    {{/if}}
</a>

_delimiter_6nomlrqbt5x
res/templates/fields/enum/edit.tpl
<select
    data-name="{{name}}"
    class="form-control main-element {{#if nativeSelect}} native-select {{/if}}"
>
    {{options
        params.options value
        scope=scope
        field=name
        translatedOptions=translatedOptions
        includeMissingOption=true
        styleMap=styleMap
    }}
</select>

_delimiter_6nomlrqbt5x
res/templates/fields/enum/detail.tpl
{{#if isNotEmpty}}
{{#if style}}
<span class="{{class}}-{{style}}"
>{{/if}}{{valueTranslated}}{{#if style}}</span>{{/if}}
{{else}}
{{#if valueIsSet}}
<span class="none-value">{{translate 'None'}}</span>
{{else}}
<span class="loading-value"></span>
{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/email-address/edit.tpl
<input
	type="email"
	class="main-element form-control"
	data-name="{{name}}"
	value="{{value}}"
	{{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}
	autocomplete="espo-{{name}}"
>

_delimiter_6nomlrqbt5x
res/templates/fields/email/list.tpl
{{#if isErased}}
    {{value}}
{{else}}
{{#unless isInvalid}}
    <a
        role="button"
        tabindex="0"
        data-email-address="{{value}}"
        data-action="mailTo"
        title="{{value}}"
        class="selectable text-default"
        {{#if isOptedOut}}style="text-decoration: line-through;"{{/if}}
    >{{value}}</a>
{{else}}
    <span title="{{value}}" style="text-decoration: line-through;">{{value}}</span>
{{/unless}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/email/edit.tpl
<div>
{{#each emailAddressData}}
    <div class="input-group email-address-block {{#if ../onlyPrimary}} only-primary {{/if}}">
        <input
            type="email"
            class="form-control email-address{{#if optOut}} text-strikethrough{{/if}}{{#if invalid}} text-danger{{/if}}"
            value="{{emailAddress}}" autocomplete="espo-{{../name}}"
            maxlength={{../itemMaxLength}}
        >
        {{#unless ../onlyPrimary}}
        <span class="input-group-btn">
            <button
                class="btn btn-default btn-icon email-property{{#if primary}} active{{/if}} hidden"
                type="button"
                data-action="switchEmailProperty"
                data-property-type="primary"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Primary' scope='EmailAddress'}}"
            >
                <span class="fas fa-star fa-sm{{#unless primary}} text-muted{{/unless}}"></span>
            </button>
            <button
                class="btn btn-default btn-icon email-property{{#if optOut}} active{{/if}}"
                type="button"
                data-action="switchEmailProperty"
                data-property-type="optOut"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Opted Out' scope='EmailAddress'}}"
            >
                <span class="fas fa-ban{{#unless optOut}} text-muted{{/unless}}"></span>
            </button>
            <button
                class="btn btn-default btn-icon radius-right email-property{{#if invalid}} active{{/if}}"
                type="button"
                data-action="switchEmailProperty"
                data-property-type="invalid"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Invalid' scope='EmailAddress'}}"
            >
                <span class="fas fa-exclamation-circle{{#unless invalid}} text-muted{{/unless}}"></span>
            </button>
            <button
                class="btn btn-link btn-icon hidden"
                type="button"
                tabindex="-1"
                data-action="removeEmailAddress"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Remove'}}"
            >
                <span class="fas fa-times"></span>
            </button>
        </span>
        {{/unless}}
    </div>
{{/each}}
</div>

{{#unless onlyPrimary}}
    <button
        class="btn btn-default btn-icon"
        type="button"
        data-action="addEmailAddress"
    ><span class="fas fa-plus"></span></button>
{{/unless}}

_delimiter_6nomlrqbt5x
res/templates/fields/email/detail.tpl
{{#if emailAddressData}}
    {{#each emailAddressData}}
        <div>
            {{#unless invalid}}
            {{#unless erased}}
            <a
                role="button"
                tabindex="0"
                data-email-address="{{emailAddress}}"
                data-action="mailTo"
                class="selectable"
            >
            {{/unless}}
            {{/unless}}
            <span {{#if lineThrough}}style="text-decoration: line-through"{{/if}}>{{emailAddress}}</span>
            {{#unless invalid}}
            {{#unless erased}}
            </a>
            {{/unless}}
            {{/unless}}
        </div>
    {{/each}}
{{else}}
    {{#if value}}
    <a
        role="button"
        tabindex="0"
        data-email-address="{{value}}"
        data-action="mailTo"
        class="selectable"
    >{{value}}</a>
    {{else}}
        {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
        <span class="loading-value"></span>{{/if}}
    {{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/duration/edit.tpl
<select data-name="{{name}}" class="form-control main-element">
    {{{durationOptions}}}
</select>

_delimiter_6nomlrqbt5x
res/templates/fields/datetime/edit.tpl

<div class="input-group-container-2">
<div class="input-group">
    <input class="main-element form-control numeric-text" type="text" data-name="{{name}}" value="{{date}}" autocomplete="espo-{{name}}">
    <span class="input-group-btn">
        <button type="button" class="btn btn-default btn-icon date-picker-btn" tabindex="-1"><i class="far fa-calendar"></i></button>
    </span>
</div>
<div class="input-group">
    <input
        class="form-control time-part numeric-text"
        type="text"
        data-name="{{name}}-time"
        value="{{time}}"
        autocomplete="espo-{{name}}"
        spellcheck="false"
    >
    <span class="input-group-btn time-part-btn">
        <button type="button" class="btn btn-default btn-icon time-picker-btn" tabindex="-1"><i class="far fa-clock"></i></button>
    </span>
</div>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/date/search.tpl
<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='dateSearchRanges'}}
</select>
<div class="input-group primary">
    <input
        class="main-element form-control input-sm numeric-text"
        type="text"
        data-name="{{name}}"
        value="{{dateValue}}"
        autocomplete="espo-{{name}}"
    >
    <span class="input-group-btn">
        <button
            type="button"
            class="btn btn-default btn-icon btn-sm date-picker-btn"
            tabindex="-1"
        ><i class="far fa-calendar"></i></button>
    </span>
</div>
<div class="input-group input-daterange{{#ifNotEqual searchType 'between'}} hidden{{/ifNotEqual}} additional">
    <input
        class="main-element form-control input-sm filter-from numeric-text"
        type="text"
        value="{{dateValue}}"
        autocomplete="espo-{{name}}"
    >
    <div class="input-group-addon input-sm"> – </div>
    <input
        class="main-element form-control input-sm filter-to numeric-text"
        type="text"
        value="{{dateValueTo}}"
        autocomplete="espo-{{name}}"
    >
</div>
<div class="hidden additional-number">
    <input
        class="main-element form-control input-sm number numeric-text"
        type="number"
        value="{{number}}"
        placeholder ="{{translate 'Number'}}"
        autocomplete="espo-{{name}}"
    >
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/date/list.tpl
{{#if dateValue~}}
    <span
        {{#if titleDateValue}}title="{{titleDateValue}}"{{/if}}
        class="{{#if style}} text-{{style}} {{/if}} {{#if useNumericFormat}} numeric-text {{/if}}"
    >{{dateValue}}</span>
{{~/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/date/list-link.tpl
<a
    href="#{{scope}}/view/{{model.id}}"
    class="link {{#if useNumericFormat}} numeric-text {{/if}}"
    data-id="{{model.id}}"
    title="{{value}}"
>{{dateValue}}</a>

_delimiter_6nomlrqbt5x
res/templates/fields/date/edit.tpl
<div class="input-group">
    <input class="main-element form-control numeric-text" type="text" data-name="{{name}}" value="{{dateValue}}" autocomplete="espo-{{name}}">
    <span class="input-group-btn">
        <button type="button" class="btn btn-default btn-icon date-picker-btn" tabindex="-1"><i class="far fa-calendar"></i></button>
    </span>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/date/detail.tpl
{{#if dateValue ~}}
    <span
        {{#if titleDateValue}}title="{{titleDateValue}}"{{/if}}
        class="{{#if style}} text-{{style}} {{/if}} {{#if useNumericFormat}} numeric-text {{/if}}"
    >{{dateValue}}</span>
{{~/if~}}

{{~#if isNone}}
<span class="none-value">{{translate 'None'}}</span>
{{/if~}}

{{~#if isLoading}}
<span class="loading-value"></span>
{{/if~}}

_delimiter_6nomlrqbt5x
res/templates/fields/currency/list.tpl
{{#if isNotEmpty}}
    <span title="{{value}} {{currencyValue}}"><span class="numeric-text">{{value}}</span> {{currencyValue}}</span>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/currency/list-3.tpl
{{#if isNotEmpty}}
    <span title="{{currencySymbol}}{{value}}"><span class="numeric-text">{{value}}</span> {{currencySymbol}}</span>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/currency/list-2.tpl
{{#if isNotEmpty}}
    <span title="{{currencySymbol}}{{value}}">{{currencySymbol}}<span class="numeric-text">{{value}}</span></span>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/currency/list-1.tpl
{{#if isNotEmpty}}
    <span title="{{value}} {{currencyValue}}"><span class="numeric-text">{{value}}</span> {{currencyValue}}</span>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/currency/edit.tpl
<div class="input-group input-group-currency">
    <span class="input-group-item">
        <input
            type="text"
            class="main-element form-control radius-left numeric-text"
            data-name="{{name}}"
            value="{{value}}"
            autocomplete="espo-{{name}}"
            pattern="[\-]?[0-9,.]*"
            {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}
        >
    </span>
    {{#if multipleCurrencies}}
    <span class="input-group-item">
        <select
            data-name="{{currencyFieldName}}"
            class="form-control radius-right"
        >{{{options currencyList currencyValue}}}</select>
    </span>
    {{else}}
    <span class="input-group-addon radius-right">{{defaultCurrency}}</span>
    {{/if}}
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/currency/detail.tpl
{{#if isNotEmpty}}
    <span class="numeric-text">{{value}}</span> {{currencyValue}}
{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
    <span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/currency/detail-no-currency.tpl
{{#if isNotEmpty}}
    <span class="numeric-text">{{value}}</span>
{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
    <span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/currency/detail-3.tpl
{{#if isNotEmpty}}
    <span class="numeric-text">{{value}}</span> {{currencySymbol}}
{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
    <span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/currency/detail-2.tpl
{{#if isNotEmpty}}
    {{currencySymbol}}<span class="numeric-text">{{value}}</span>
{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
    <span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/currency/detail-1.tpl
{{#if isNotEmpty}}
    <span class="numeric-text">{{value}}</span> {{currencyValue}}
{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
    <span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/colorpicker/edit.tpl
<div class="input-group colorpicker-component">
    <input
        type="text"
        class="main-element form-control"
        data-name="{{name}}"
        value="{{value}}"
        {{#if params.maxLength}}maxlength="{{params.maxLength}}"{{/if}}
        autocomplete="espo-{{name}}"
    >
    <span class="btn btn-default input-group-addon"><i></i></span>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/colorpicker/detail.tpl
{{#if isNotEmpty}}<span class="fas fa-tint" style="color: {{value}}"></span> <span>{{value}}</span>
{{else}}<span class="none-value">{{translate 'None'}}</span>{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/checklist/edit.tpl

{{#each optionDataList}}
<div class="checklist-item-container">
    <input
        type="checkbox"
        data-name="{{dataName}}"
        id="{{id}}"
        class="form-checkbox"
        {{#if isChecked}}checked{{/if}}
    >
    <label for="{{id}}" class="checklist-label">{{label}}</label>
</div>
{{/each}}
{{#unless optionDataList.length}}{{translate 'None'}}{{/unless}}

_delimiter_6nomlrqbt5x
res/templates/fields/checklist/detail.tpl

{{#each optionDataList}}
<div class="checklist-item-container">
    <input
        type="checkbox"
        data-name="{{dataName}}"
        id="{{id}}"
        class="form-checkbox"
        {{#if isChecked}} checked{{/if}}
        disabled="disabled"
    >
    <label for="{{id}}" class="checklist-label">{{label}}</label>
</div>
{{/each}}
{{#unless optionDataList.length}}<span class="none-value">{{translate 'None'}}</span>{{/unless}}

_delimiter_6nomlrqbt5x
res/templates/fields/bool/search.tpl
<select data-name="{{name}}" class="main-element form-control input-sm">
	<option value="isTrue" {{#ifEqual searchType 'isTrue'}} selected{{/ifEqual}}>{{translate 'Yes'}}</option>
	<option value="isFalse" {{#ifEqual searchType 'isFalse'}} selected{{/ifEqual}}>{{translate 'No'}}</option>
	<option value="any" {{#ifEqual searchType 'any'}} selected{{/ifEqual}}>{{translateOption 'any' field='searchRanges'}}</option>
</select>

_delimiter_6nomlrqbt5x
res/templates/fields/bool/list.tpl
<input type="checkbox" class="form-checkbox form-checkbox-simple"{{#if value}} checked{{/if}} disabled>

_delimiter_6nomlrqbt5x
res/templates/fields/bool/edit.tpl
<input type="checkbox"{{#if value}} checked{{/if}} data-name="{{name}}" class="main-element form-checkbox">

_delimiter_6nomlrqbt5x
res/templates/fields/bool/detail.tpl
{{#if valueIsSet}}<input class="form-checkbox" type="checkbox"{{#if value}} checked{{/if}} disabled>{{else}}
<span class="loading-value"></span>{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/base/search.tpl
<input
    type="text"
    class="main-element form-control input-sm"
    data-name="{{name}}"
    value="{{searchParams.value}}" {{#if params.maxLength}}
    maxlength="{{params.maxLength}}"{{/if}}{{#if params.size}}
    size="{{params.size}}"{{/if}}
    autocomplete="espo-{{name}}"
    {{#if noSpellCheck}}
    spellcheck="false"
    {{/if}}
>

_delimiter_6nomlrqbt5x
res/templates/fields/base/list.tpl
{{value}}

_delimiter_6nomlrqbt5x
res/templates/fields/base/list-link.tpl
<a
    href="#{{scope}}/view/{{model.id}}"
    class="link"
    data-id="{{model.id}}"
    title="{{value}}"
>{{#if value}}{{value}}{{else}}{{translate 'None'}}{{/if}}</a>

_delimiter_6nomlrqbt5x
res/templates/fields/base/edit.tpl
<input
	type="text"
	class="main-element form-control"
	data-name="{{name}}"
	value="{{value}}"
	{{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}
	autocomplete="espo-{{name}}"
    {{#if noSpellCheck}}
    spellcheck="false"
    {{/if}}
>

_delimiter_6nomlrqbt5x
res/templates/fields/base/detail.tpl
{{value}}
_delimiter_6nomlrqbt5x
res/templates/fields/barcode/detail.tpl
{{#if isNotEmpty}}

{{#if isSvg}}
<svg class="barcode"></svg>
{{else}}
<div class="barcode"></div>
{{/if}}

{{else}}
{{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}<span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/attachments-multiple/list.tpl
{{#if value}}
    {{{value}}}
{{/if}}
_delimiter_6nomlrqbt5x
res/templates/fields/attachments-multiple/edit.tpl
<div class="attachment-upload">
    <div class="clearfix attachment-control">
        {{#unless uploadFromFileSystemDisabled}}
        <div class="pull-left">
            <label class="attach-file-label" title="{{translate 'Attach File'}}" tabindex="0">
                <span class="btn btn-default btn-icon"><span class="fas fa-paperclip"></span></span>
                <input
                    type="file"
                    class="file pull-right"
                    multiple
                    {{#if acceptAttribute}}accept="{{acceptAttribute}}"{{/if}}
                    tabindex="-1"
                >
            </label>
        </div>
        {{/unless}}

        {{#if sourceList.length}}
        <div class="pull-left dropdown">
            <button class="btn btn-default btn-icon dropdown-toggle" type="button" data-toggle="dropdown">
                <span class="fas fa-file"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
            {{#each sourceList}}
                <li><a
                        role="button"
                        tabindex="0"
                        class="action"
                        data-action="insertFromSource"
                        data-name="{{./this}}"
                    >{{translate this category='insertFromSourceLabels' scope='Attachment'}}</a></li>
            {{/each}}
            </ul>
        </div>
        {{/if}}
    </div>
    <div class="attachments"></div>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/attachments-multiple/detail.tpl
{{#if value}}
    {{{value}}}
{{else}}
    {{#if valueIsSet}}
        <span class="none-value">{{translate 'None'}}</span>
    {{else}}
        <span class="loading-value"></span>
    {{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/array/search.tpl

<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='searchRanges'}}
</select>
<div class="input-container">
    <input class="main-element" type="text" autocomplete="espo-off">
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/array/list.tpl
{{#unless isEmpty}}{{{value}}}{{/unless}}

_delimiter_6nomlrqbt5x
res/templates/fields/array/list-link.tpl
<a
    href="#{{scope}}/view/{{model.id}}"
    class="link"
    data-id="{{model.id}}"
    title="{{value}}"
>{{#if value}}{{{value}}}{{else}}{{translate 'None'}}{{/if}}</a>

_delimiter_6nomlrqbt5x
res/templates/fields/array/edit.tpl
<div
    class="link-container list-group{{#if keepItems}} no-input{{/if}}"
>{{#each itemHtmlList}}{{{./this}}}{{/each}}</div>
<div class="array-control-container">
{{#if hasAdd}}
<button
    class="btn btn-default btn-block"
    type="button"
    data-action="showAddModal"
>{{translate 'Add'}}</button>
{{/if}}
{{#if allowCustomOptions}}
<div class="input-group">
    <input
        class="main-element form-control select"
        type="text"
        autocomplete="espo-{{name}}"
        placeholder="{{#if this.options}}{{translate 'Select'}}{{else}}{{translate 'typeAndPressEnter' category='messages'}}{{/if}}"
        {{#if maxItemLength}} maxlength="{{maxItemLength}}"{{/if}}
    >
    <span class="input-group-btn">
        <button
            data-action="addItem"
            class="btn btn-default btn-icon"
            type="button"
            tabindex="-1"
            title="{{translate 'Add Item'}}"
        ><span class="fas fa-plus"></span></button>
    </span>
</div>
{{/if}}
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/array/detail.tpl
{{#unless isEmpty}}{{{value}}}{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}<span class="loading-value"></span>{{/if}}
{{/unless}}

_delimiter_6nomlrqbt5x
res/templates/fields/address/search.tpl

<input type="text" class="main-element form-control input-sm" data-name="{{name}}" value="{{searchData.value}}" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}{{#if params.size}} size="{{params.size}}"{{/if}} autocomplete="espo-{{name}}">

_delimiter_6nomlrqbt5x
res/templates/fields/address/list-link.tpl
{{#if formattedAddress}}
    <a
        href="#{{scope}}/view/{{model.id}}"
        class="link"
        data-id="{{model.id}}"
    >{{breaklines formattedAddress}}</a>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/fields/address/edit.tpl
<textarea
    class="form-control auto-height"
    data-name="{{name}}Street"
    rows="1" placeholder="{{translate 'Street'}}"
    autocomplete="espo-street"
    maxlength="{{streetMaxLength}}"
    style="resize: none;"
>{{streetValue}}</textarea>
<div class="row">
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="{{name}}City" value="{{cityValue}}" placeholder="{{translate 'City'}}" autocomplete="espo-city" maxlength="{{cityMaxLength}}">
    </div>
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="{{name}}State" value="{{stateValue}}" placeholder="{{translate 'State'}}" autocomplete="espo-state" maxlength="{{stateMaxLength}}">
    </div>
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="{{name}}PostalCode" value="{{postalCodeValue}}" placeholder="{{translate 'PostalCode'}}" autocomplete="espo-postalCode" maxlength="{{postalCodeMaxLength}}" spellcheck="false">
    </div>
</div>
<input type="text" class="form-control" data-name="{{name}}Country" value="{{countryValue}}" placeholder="{{translate 'Country'}}" autocomplete="espo-country" maxlength="{{countryMaxLength}}">

_delimiter_6nomlrqbt5x
res/templates/fields/address/edit-4.tpl
<textarea
    class="form-control auto-height"
    data-name="{{name}}Street"
    rows="1" placeholder="{{translate 'Street'}}"
    autocomplete="espo-street"
    maxlength="{{streetMaxLength}}"
    style="resize: none;"
>{{streetValue}}</textarea>
<input type="text" class="form-control" data-name="{{name}}City" value="{{cityValue}}" placeholder="{{translate 'City'}}" autocomplete="espo-city" maxlength="{{cityMaxLength}}">
<div class="row">
    <div class="col-sm-5 col-xs-5">
        <input type="text" class="form-control" data-name="{{name}}Country" value="{{countryValue}}" placeholder="{{translate 'Country'}}" autocomplete="espo-country" maxlength="{{countryMaxLength}}">
    </div>
    <div class="col-sm-3 col-xs-3">
        <input type="text" class="form-control" data-name="{{name}}State" value="{{stateValue}}" placeholder="{{translate 'State'}}" autocomplete="espo-state" maxlength="{{stateMaxLength}}">
    </div>
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="{{name}}PostalCode" value="{{postalCodeValue}}" placeholder="{{translate 'PostalCode'}}" autocomplete="espo-postalCode" maxlength="{{postalCodeMaxLength}}" spellcheck="false">
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/address/edit-3.tpl
<input type="text" class="form-control auto-height" data-name="{{name}}Country" value="{{countryValue}}" placeholder="{{translate 'Country'}}" autocomplete="espo-country" maxlength="{{countryMaxLength}}">
<div class="row">
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="{{name}}PostalCode" value="{{postalCodeValue}}" placeholder="{{translate 'PostalCode'}}" autocomplete="espo-postalCode" maxlength="{{postalCodeMaxLength}}" spellcheck="false">
    </div>
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="{{name}}State" value="{{stateValue}}" placeholder="{{translate 'State'}}" autocomplete="espo-state" maxlength="{{stateMaxLength}}">
    </div>
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="{{name}}City" value="{{cityValue}}" placeholder="{{translate 'City'}}" autocomplete="espo-city" maxlength="{{cityMaxLength}}">
    </div>
</div>
<textarea
    class="form-control auto-height"
    data-name="{{name}}Street"
    rows="1" placeholder="{{translate 'Street'}}"
    autocomplete="espo-street"
    maxlength="{{streetMaxLength}}"
    style="resize: none;"
>{{streetValue}}</textarea>

_delimiter_6nomlrqbt5x
res/templates/fields/address/edit-2.tpl
<textarea
    class="form-control auto-height"
    data-name="{{name}}Street"
    rows="1" placeholder="{{translate 'Street'}}"
    autocomplete="espo-street"
    maxlength="{{streetMaxLength}}"
    style="resize: none;"
>{{streetValue}}</textarea>
<div class="row">
    <div class="col-sm-6 col-xs-6">
        <input type="text" class="form-control" data-name="{{name}}PostalCode" value="{{postalCodeValue}}" placeholder="{{translate 'PostalCode'}}" autocomplete="espo-postalCode" maxlength="{{postalCodeMaxLength}}" spellcheck="false">
    </div>
    <div class="col-sm-6 col-xs-6">
        <input type="text" class="form-control" data-name="{{name}}City" value="{{cityValue}}" placeholder="{{translate 'City'}}" autocomplete="espo-city" maxlength="{{cityMaxLength}}">
    </div>
</div>
<div class="row">
    <div class="col-sm-6 col-xs-6">
        <input type="text" class="form-control" data-name="{{name}}State" value="{{stateValue}}" placeholder="{{translate 'State'}}" autocomplete="espo-state" maxlength="{{stateMaxLength}}">
    </div>
    <div class="col-sm-6 col-xs-6">
        <input type="text" class="form-control" data-name="{{name}}Country" value="{{countryValue}}" placeholder="{{translate 'Country'}}" autocomplete="espo-country" maxlength="{{countryMaxLength}}">
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/fields/address/edit-1.tpl
<textarea
    class="form-control auto-height"
    data-name="{{name}}Street"
    rows="1" placeholder="{{translate 'Street'}}"
    autocomplete="espo-street"
    maxlength="{{streetMaxLength}}"
    style="resize: none;"
>{{streetValue}}</textarea>
<div class="row">
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="{{name}}City" value="{{cityValue}}" placeholder="{{translate 'City'}}" autocomplete="espo-city" maxlength="{{cityMaxLength}}">
    </div>
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="{{name}}State" value="{{stateValue}}" placeholder="{{translate 'State'}}" autocomplete="espo-state" maxlength="{{stateMaxLength}}">
    </div>
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="{{name}}PostalCode" value="{{postalCodeValue}}" placeholder="{{translate 'PostalCode'}}" autocomplete="espo-postalCode" maxlength="{{postalCodeMaxLength}}" spellcheck="false">
    </div>
</div>
<input type="text" class="form-control" data-name="{{name}}Country" value="{{countryValue}}" placeholder="{{translate 'Country'}}" autocomplete="espo-country" maxlength="{{countryMaxLength}}">

_delimiter_6nomlrqbt5x
res/templates/fields/address/detail.tpl
{{#if formattedAddress}}
{{breaklines formattedAddress}}
{{/if}}

{{#if isNone}}
<span class="none-value">{{translate 'None'}}</span>
{{/if}}

{{#if isLoading}}
<span class="loading-value"></span>
{{/if}}

{{#if viewMap}}
<div><a
    href="{{viewMapLink}}"
    data-action="viewMap"
    class="small"
    style="user-select: none;"
>{{translate 'View on Map'}}</a></div>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/external-account/oauth2.tpl
<div class="button-container">
    <div class="btn-group">
        <button class="btn btn-primary btn-xs-wide" data-action="save">{{translate 'Save'}}</button>
        <button class="btn btn-default btn-xs-wide" data-action="cancel">{{translate 'Cancel'}}</button>
    </div>
</div>

<div class="row">
    <div class="col-sm-6">
        <div>
            <div class="cell form-group" data-name="enabled">
                <label
                    class="control-label"
                    data-name="enabled"
                >{{translate 'enabled' scope='Integration' category='fields'}}</label>
                <div class="field" data-name="enabled">{{{enabled}}}</div>
            </div>
        </div>
        <div class="data-panel">
            <button
                type="button"
                class="btn btn-danger {{#if isConnected}}hidden{{/if}}"
                data-action="connect"
            >{{translate 'Connect' scope='ExternalAccount'}}</button>
            <span
                class="connected-label label label-success {{#unless isConnected}}hidden{{/unless}}"
            >{{translate 'Connected' scope='ExternalAccount'}}</span>
        </div>
    </div>
    <div class="col-sm-6">
        {{#if helpText}}
        <div class="well">
            {{{helpText}}}
        </div>
        {{/if}}
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/external-account/index.tpl
<div class="page-header"><h3>{{translate 'ExternalAccount' category='scopeNamesPlural'}}</h3></div>

<div class="row">
    <div id="external-account-menu" class="col-sm-3">
    <ul class="list-group list-group-panel{{#unless externalAccountListCount}} hidden{{/unless}}">
    {{#each externalAccountList}}
        <li class="list-group-item"><a
            role="button"
            tabindex="0"
            class="external-account-link"
            data-id="{{id}}"
        >{{id}}</a></li>
    {{/each}}
    </ul>
    {{#unless externalAccountListCount}}
        {{translate 'No Data'}}
    {{/unless}}
    </div>

    <div id="external-account-panel" class="col-sm-9">
        <h4 id="external-account-header" style="margin-top: 0px;"></h4>
        <div id="external-account-content">
            {{{content}}}
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/export/modals/idle.tpl
<div class="record no-side-margin">{{{record}}}</div>

<div class="well info-text">{{complexText infoText}}</div>

<div class="margin-top download-container hidden">
	<button type="button" class="btn btn-default download-button" data-action="download">{{translate 'Download'}}</button>
</div>

_delimiter_6nomlrqbt5x
res/templates/export/modals/export.tpl
<div class="record no-side-margin">{{{record}}}</div>

_delimiter_6nomlrqbt5x
res/templates/event/fields/name-for-history/list-link.tpl
<a
    href="#{{model.entityType}}/view/{{model.id}}"
    class="link"
    data-id="{{model.id}}"
    title="{{value}}"
    {{#if strikethrough}}style="text-decoration: line-through;"{{/if}}
>{{#if value}}{{value}}{{else}}{{translate 'None'}}{{/if}}</a>

_delimiter_6nomlrqbt5x
res/templates/errors/404.tpl
<div class="container">
    <div class="panel panel-default block-center-md margin-top-2x">
        <div class="panel-body">
            <h1 class="text-5em text-soft margin-bottom-4x">404</h1>
            <p class="text-large">{{translate 'error404' category='messages'}}</p>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/errors/403.tpl
<div class="container">
    <div class="panel panel-default block-center-md margin-top-2x">
        <div class="panel-body">
            <h1 class="text-5em text-soft margin-bottom-4x">403</h1>
            <p class="text-large">{{translate 'error403' category='messages'}}</p>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/email-template/fields/insert-field/edit.tpl
<div class="row">
    <div class="col-sm-4 col-xs-6">
        <select class="form-control" data-name="entityType"></select>
    </div>
    <div class="col-sm-6 col-xs-6">
        <select class="form-control" data-name="field"></select>
    </div>
    <div class="col-sm-2 col-xs-6">
        <button class="btn btn-default" type="button" data-action="insert">{{translate 'Insert'}}</button>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/email-template/fields/insert-field/detail.tpl

_delimiter_6nomlrqbt5x
res/templates/email-folder/list-side.tpl
<ul class="list-group list-group-side list-group-no-border folder-list">
    <li
        data-id="all"
        class="list-group-item{{#ifEqual 'all' selectedFolderId}} selected{{/ifEqual}} droppable"
    >
        <a
            href="#Email/list/folder=all"
            data-action="selectFolder"
            data-id="all"
            class="side-link"
        ><span class="item-icon-container"><span class="far fa-hdd"></span></span><span>{{translate 'all' category='presetFilters' scope='Email'}}</span></a>
    </li>
    {{#each collection.models}}
    <li
        data-id="{{get this 'id'}}"
        class="list-group-item {{#ifAttrEquals this 'id' ../selectedFolderId}} selected {{/ifAttrEquals}}{{#if droppable}} droppable {{/if}}{{#if groupStart}} group-start {{/if}}"
    >
        <a
            href="#Email/list/folder={{get this 'id'}}"
            data-action="selectFolder"
            data-id="{{get this 'id'}}"
            class="side-link pull-right count"
        ></a>
        <a
            href="#Email/list/folder={{get this 'id'}}"
            data-action="selectFolder"
            data-id="{{get this 'id'}}"
            class="side-link"
            {{#if title}}title="{{title}}"{{/if}}
        ><span class="item-icon-container"><span class="{{iconClass}}"></span></span><span>{{get this 'name'}}</span></a>
    </li>
    {{/each}}
</ul>

_delimiter_6nomlrqbt5x
res/templates/email-folder/modals/select-folder.tpl
<ul class="list-group no-side-margin">
    {{#each folderDataList}}
    <li data-id="{{id}}" class="list-group-item">
        <a
            role="button"
            tabindex="0"
            data-action="selectFolder"
            data-id="{{id}}"
            data-name="{{name}}"
            class="side-link text-bold {{#if disabled}} disabled text-muted {{/if}}"
        ><span class="item-icon-container"><span class="{{iconClass}}"></span></span><span class="text-default">{{name}}</span></a>
    </li>
    {{/each}}
</ul>

_delimiter_6nomlrqbt5x
res/templates/email-account/modals/select-folder.tpl
{{#unless folders}}
    {{translate 'No Data'}}
{{/unless}}
<ul class="list-group no-side-margin array-add-list-group">
{{#each folders}}
    <li class="list-group-item">
        <a role="button" data-value="{{./this}}" data-action="select" class="text-bold">
        {{./this}}
        </a>
    </li>
{{/each}}
</ul>

_delimiter_6nomlrqbt5x
res/templates/email-account/fields/folder/edit.tpl
<div class="input-group">
    <input class="main-element form-control" type="text" data-name="{{name}}" value="{{value}}" autocomplete="espo-{{name}}">
    <span class="input-group-btn">
        <button data-action="selectFolder" class="btn btn-default" type="button" tabindex="-1"><i class="fas fa-angle-up"></i></button>
    </span>
</div>

_delimiter_6nomlrqbt5x
res/templates/email/list.tpl
<div class="page-header">{{{header}}}</div>
<div class="search-container">{{{search}}}</div>

<div class="row">
    {{#unless foldersDisabled}}
    <div class="left-container{{#unless foldersDisabled}} col-md-2 col-sm-3{{else}} col-md-12{{/unless}}">
        <div class="folders-container">{{{folders}}}</div>
    </div>
    {{/unless}}
    <div class="list-container{{#unless foldersDisabled}} col-md-10 col-sm-9{{else}} col-md-12{{/unless}}">{{{list}}}</div>
</div>

_delimiter_6nomlrqbt5x
res/templates/email/fields/subject/list-link.tpl
<span>
   <span>
         <a
             href="#{{scope}}/view/{{model.id}}"
             class="link {{#if style}}text-{{style}}{{/if}} {{#unless isRead}} text-bold {{/unless}}"
             data-id="{{model.id}}"
             title="{{value}}"
         >{{value}}</a>
    </span>
    {{#if hasIcon}}
        <span class="list-icon-container" data-icon-count="{{iconCount}}">
            {{#if hasAttachment}}
                <a
                    role="button"
                    tabindex="0"
                    data-action="showAttachments"
                    class="text-muted"
                ><span
                    class="fas fa-paperclip small"
                    title="{{translate 'hasAttachment' category='fields' scope='Email'}}"
                ></span></a>
            {{/if}}
            {{#if isAutoReply}}
                <span
                    class="fas fas fa-robot small text-muted"
                    title="{{translate 'isAutoReply' category='fields' scope='Email'}}"
                ></span>
            {{/if}}
        </span>
    {{/if}}
</span>

_delimiter_6nomlrqbt5x
res/templates/email/fields/select-template/edit.tpl
<div class="input-group">
    <input
        class="main-element form-control"
        type="text"
        data-name="{{nameName}}"
        value="{{nameValue}}"
        autocomplete="espo-{{name}}"
        spellcheck="false"
    >
    <span class="input-group-btn">
        <button
            data-action="selectLink"
            class="btn btn-default btn-icon"
            type="button"
            tabindex="-1"
            title="{{translate 'Select'}}"
        ><i class="fas fa-angle-up"></i></button>
    </span>
</div>
<input type="hidden" data-name="{{idName}}" value="{{idValue}}">

_delimiter_6nomlrqbt5x
res/templates/email/fields/person-string-data/list.tpl
<span class="list-icon-container pull-right"{{#unless isReplied}} style="visibility: hidden;"{{/unless}}>
    <span class="fas icon-is-replied fa-sm text-muted icon-flip-horizontal" title="{{translate 'isReplied' category='fields' scope='Email'}}"></span>
</span>
<span title="{{value}}">{{value}}</span>
_delimiter_6nomlrqbt5x
res/templates/email/fields/person-string-data/list-for-expanded.tpl
<span title="{{value}}">{{value}}</span>
<span class="list-icon-container"{{#unless isReplied}} style="display: none;"{{/unless}}>
    <span
        class="fas icon-is-replied fa-sm text-muted icon-flip-horizontal"
        title="{{translate 'isReplied' category='fields' scope='Email'}}"
    ></span>
</span>

_delimiter_6nomlrqbt5x
res/templates/email/fields/icon/detail.tpl
<span class="fas fa-envelope action text-muted" data-action="quickView" data-id="{{model.id}}" style="cursor: pointer; margin-left: -7px; top: 2px;" title="{{translate 'View'}}"></span>
_delimiter_6nomlrqbt5x
res/templates/email/fields/has-attachment/detail.tpl
{{#if value~}}
<a
    role="button"
    tabindex="0"
    data-action="show"
    class="text-soft"
><span
    class="fas fa-paperclip{{#if isSmall}} small{{/if}}"
    title="{{translate 'View Attachments' scope='Email'}}"
></span></a>
{{~/if~}}

_delimiter_6nomlrqbt5x
res/templates/email/fields/from-email-address/detail.tpl
{{nameValue}}

_delimiter_6nomlrqbt5x
res/templates/email/fields/email-address-varchar/edit.tpl
<div class="link-container list-group"></div>
{{#if hasSelectAddress}}
    <div class="input-group">
        <input
            class="form-control"
            type="email"
            autocomplete="espo-{{name}}"
            spellcheck="false"
            maxlength="{{maxLength}}"
        >
        <div class="input-group-btn">
            <button
                data-action="selectAddress"
                class="btn btn-default btn-icon"
                type="button"
                tabindex="-1"
                title="{{translate 'Select'}}"
            ><i class="fas fa-angle-up"></i></button>
        </div>
    </div>
{{else}}
    <input
        class="form-control"
        type="email"
        autocomplete="espo-{{name}}"
        spellcheck="false"
        maxlength="{{maxLength}}"
        title="{{translate 'Select'}}"
    >
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/email/fields/email-address-varchar/detail.tpl
{{#if value}}
    {{{value}}}
{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>
    {{else}}<span class="loading-value"></span>{{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/email/fields/create-event/detail.tpl
<button class="btn btn-default" data-action="createEvent">{{translate 'Create Meeting' scope='Meeting'}}</button>

_delimiter_6nomlrqbt5x
res/templates/email/fields/compose-from-address/edit.tpl
{{#if list.length}}
    <select data-name="{{name}}" class="form-control main-element">
    {{#each list}}
        <option value="{{./this}}"{{#ifEqual ../value this}} selected{{/ifEqual}}>{{./this}}</option>
    {{/each}}
</select>
{{else}}
    {{{noSmtpMessage}}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/dashlets/record-list/options.tpl

_delimiter_6nomlrqbt5x
res/templates/dashlets/options/base.tpl
<div class="no-side-margin">

<form id="dashlet-options">
    <div class="middle record no-side-margin">{{{record}}}</div>
</form>

</div>

_delimiter_6nomlrqbt5x
res/templates/attachment/fields/name/detail.tpl

<span class="fas fa-paperclip small"></span> <a href="{{url}}" target="_blank">{{value}}</a>

_delimiter_6nomlrqbt5x
res/templates/admin/index.tpl
<div class="page-header"><h3>{{translate 'Administration' scope='Admin'}}</h3></div>

<div class="admin-content">
    <div class="row">
        <div class="col-md-7">
            <div class="admin-search-container">
                <input
                    type="text"
                    maxlength="64"
                    placeholder="{{translate 'Search'}}"
                    data-name="quick-search"
                    class="form-control"
                    spellcheck="false"
                >
            </div>
            <div class="admin-tables-container">
                {{#each panelDataList}}
                <div class="admin-content-section" data-index="{{@index}}">
                    <h4>{{label}}</h4>
                    <table class="table table-admin-panel" data-name="{{name}}">
                        {{#each itemList}}
                        <tr class="admin-content-row" data-index="{{@index}}">
                            <td>
                                <div>
                                {{#if iconClass}}
                                <span class="icon {{iconClass}}"></span>
                                {{/if}}
                                <a
                                    {{#if url}}href="{{url}}"{{else}}role="button"{{/if}}
                                    tabindex="0"
                                    {{#if action}} data-action="{{action}}"{{/if}}
                                >{{label}}</a>
                                </div>
                            </td>
                            <td>{{translate description scope='Admin' category='descriptions'}}</td>
                        </tr>
                        {{/each}}
                    </table>
                </div>
                {{/each}}
                <div class="no-data hidden">{{translate 'No Data'}}</div>
            </div>
        </div>
        <div class="col-md-5 admin-right-column">
            <div class="notifications-panel-container">{{{notificationsPanel}}}</div>

            {{#unless iframeDisabled}}
            <iframe
                src="{{iframeUrl}}"
                style="width: 100%; height: {{iframeHeight}}px"
                frameborder="0"
                webkitallowfullscreen mozallowfullscreen allowfullscreen
            ></iframe>
            {{/unless}}
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/upgrade/ready.tpl

<p class="text-danger">
    {{complexText text inline=true}}
</p>

_delimiter_6nomlrqbt5x
res/templates/admin/upgrade/index.tpl
<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span></span></span>
{{translate 'Upgrade' scope='Admin'}}</h3></div>

<div class="row">
<div class="col-md-8">

<div class="panel panel-danger notify">
    <div class="panel-body">
        <p class="notify-text">
            {{versionMsg}}
            <br><br>
            {{complexText infoMsg inline=true}}
            <br><br>
            {{backupsMsg}}
        </p>
    </div>
</div>

<div class="panel panel-default upload">
    <div class="panel-heading">
        <h4 class="panel-title">{{translate 'selectUpgradePackage' scope='Admin' category="messages"}}</h4>
    </div>
    <div class="panel-body">
        <p class="text-danger" style="font-weight: 600;">{{{upgradeRecommendation}}}</p>
        <p class="">
            {{complexText downloadMsg inline=true}}
        </p>
        <div>
            <input type="file" name="package" accept="application/zip">
        </div>
        <div class="message-container text-danger" style="height: 20px; margin-bottom: 10px; margin-top: 10px;"></div>
        <div class="buttons-container">
            <button class="btn btn-primary disabled" disabled="disabled" data-action="upload">{{translate 'Upload' scope='Admin'}}</button>
        </div>
    </div>
</div>

</div>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/upgrade/done.tpl

<p class="text-success">
    {{complexText text inline=true}}
</p>

_delimiter_6nomlrqbt5x
res/templates/admin/template-manager/index.tpl
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

_delimiter_6nomlrqbt5x
res/templates/admin/template-manager/edit.tpl
<div class="page-header">
    <h4>{{{title}}}</h4>
</div>

<div class="button-container">
    <div class="btn-group">
        <button class="btn btn-primary btn-xs-wide" data-action="save">{{translate 'Save'}}</button>
        <button class="btn btn-default btn-xs-wide" data-action="cancel">{{translate 'Cancel'}}</button>
        <button class="btn btn-default btn-xs-wide" data-action="resetToDefault"
            >{{translate 'Reset to Default' scope='Admin'}}</button>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body panel-body-form">
        {{#if hasSubject}}
            <div class="row">
                <div class="cell col-sm-12 form-group">
                    <div class="field subject-field">{{{subjectField}}}</div>
                </div>
            </div>
        {{/if}}
        <div class="row">
            <div class="cell col-sm-12 form-group">
                <div class="field body-field">{{{bodyField}}}</div>
            </div>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/system-requirements/index.tpl
<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span></span></span>
{{translate 'System Requirements' scope='Admin'}}</h3></div>

{{#if noAccess}}
    <div class="panel panel-default">
        <div class="panel-body">
            {{translate 'error403' category='messages'}}
        </div>
    </div>
{{else}}
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
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/admin/settings/headers/page.tpl
<h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span></span></span>
{{translate viewObject.options.label category='labels' scope='Admin'}}</h3>

_delimiter_6nomlrqbt5x
res/templates/admin/panels/notifications.tpl
{{#if notificationList}}
    <div class="panel panel-danger">
        <div class="panel-body">
            <div class="list-container">
                <div class="list-group list list-expanded">
                {{#each notificationList}}
                    <div data-id="{{id}}" class="list-group-item notification-item">
                        <div class="text-danger complex-text">{{complexText message}}</div>
                    </div>
                {{/each}}
                </div>
            </div>
        </div>
    </div>
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/admin/link-manager/index.tpl
<div class="page-header">
    <h3>
        <a href="#Admin">{{translate 'Administration'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        <a href="#Admin/entityManager">{{translate 'Entity Manager' scope='Admin'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        <a href="#Admin/entityManager/scope={{scope}}">{{translate scope category='scopeNames'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        {{translate 'Relationships' scope='EntityManager'}}
    </h3>
</div>

<div class="button-container">
    {{#if isCreatable}}
        <button class="btn btn-default btn-wide" data-action="createLink">
            <span class="fas fa-plus fa-sm"></span><span>{{translate 'Create Link' scope='Admin'}}</span>
        </button>
    {{/if}}
</div>

{{#if linkDataList.length}}
<div class="margin-bottom-2x margin-top">
    <input
        type="text"
        maxlength="64"
        placeholder="{{translate 'Search'}}"
        data-name="quick-search"
        class="form-control"
        spellcheck="false"
    >
</div>
{{/if}}

<table class="table table-panel table-hover">
    {{#unless linkDataList.length}}
    <tr>
        <td>
            {{translate 'No Data'}}
        </td>
    </tr>
    {{else}}
    <thead>
        <tr>
            <th style="width: 20%;">{{translate 'linkForeign' category='fields' scope='EntityManager'}}</th>
            <th style="width: 20%;">
                {{translate 'linkType' category='fields' scope='EntityManager'}}
            </th>
            <th style="width: 20%;">
                {{translate 'link' category='fields' scope='EntityManager'}}
            </th>
            <th style="width: 20%;">
                {{translate 'entityForeign' category='fields' scope='EntityManager'}}
            </th>
            <th style="width: 10%"></th>
        </tr>
    </thead>
    {{/unless}}
    {{#each linkDataList}}
    <tr data-link="{{link}}" class="link-row">
        <td style="">
            <span title="{{translate linkForeign category='links' scope=entityForeign}}">
                {{linkForeign}}
            </span>
        </td>
        <td>
            <span style="color: var(--gray-soft); font-weight: 600;">
            {{translateOption type field='linkType' scope='EntityManager'}}
            </span>
        </td>
        <td>
            <span title="{{translate link category='links' scope=entity}}">
                {{link}}
            </span>
        </td>
        <td>
            {{translate entityForeign category='scopeNames'}}
        </td>
        <td style="text-align: right">
            {{#if hasDropdown}}
                <div class="btn-group row-dropdown-group">
                    <button
                        class="btn btn-link btn-sm dropdown-toggle"
                        data-toggle="dropdown"
                    ><span class="caret"></span></button>
                    <ul class="dropdown-menu pull-right">
                        {{#if isEditable}}
                            <li>
                                <a
                                    role="button"
                                    tabindex="0"
                                    data-action="editLink"
                                    data-link="{{link}}"
                                >{{translate 'Edit'}}</a>
                            </li>
                        {{/if}}
                        {{#if hasEditParams}}
                            <li>
                                <a
                                    role="button"
                                    tabindex="0"
                                    data-action="editParams"
                                    data-link="{{link}}"
                                >{{translate 'Parameters' scope='EntityManager'}}</a>
                            </li>
                        {{/if}}
                        {{#if isRemovable}}
                        <li>
                            <a
                                role="button"
                                tabindex="0"
                                data-action="removeLink"
                                data-link="{{link}}"
                            >{{translate 'Remove'}}</a>
                        </li>
                        {{/if}}
                    </ul>
                </div>
            {{/if}}
        </td>
    </tr>
    {{/each}}
</table>

<div class="no-data hidden">{{translate 'No Data'}}</div>

_delimiter_6nomlrqbt5x
res/templates/admin/link-manager/modals/edit.tpl
<div class="panel panel-default no-side-margin">
    <div class="panel-body">

        <div class="row">
            <div class="cell form-group col-md-4" data-name="entity">
                <label class="control-label" data-name="entity">{{translate 'entity' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="entity">
                    {{{entity}}}
                </div>
            </div>
            <div class="cell form-group col-md-4" data-name="linkType">
                <label class="control-label" data-name="linkType">{{translate 'linkType' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="linkType">
                    {{{linkType}}}
                </div>
            </div>
            <div class="cell form-group col-md-4" data-name="entityForeign">
                <label class="control-label" data-name="entityForeign">{{translate 'entityForeign' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="entityForeign">
                    {{{entityForeign}}}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="cell form-group col-md-4" data-name="linkForeign">
                <label class="control-label" data-name="linkForeign">{{translate 'name' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="linkForeign">
                    {{{linkForeign}}}
                </div>
            </div>
            <div class="cell form-group col-md-4" data-name="relationName">
                {{#if relationName}}
                <label class="control-label" data-name="relationName">{{translate 'relationName' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="relationName">
                    {{{relationName}}}
                </div>
                {{/if}}
            </div>
            <div class="cell form-group col-md-4" data-name="link">
                <label class="control-label" data-name="link">{{translate 'name' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="link">
                    {{{link}}}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="cell form-group col-md-4" data-name="labelForeign">
                <label class="control-label" data-name="labelForeign">{{translate 'label' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="labelForeign">
                    {{{labelForeign}}}
                </div>
            </div>
            <div class="cell form-group col-md-4"></div>
            <div class="cell form-group col-md-4" data-name="label">
                <label class="control-label" data-name="label">{{translate 'label' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="label">
                    {{{label}}}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="cell form-group col-md-4" data-name="linkMultipleFieldForeign">
                <label class="control-label" data-name="linkMultipleFieldForeign">{{translate 'linkMultipleField' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="linkMultipleFieldForeign">
                    {{{linkMultipleFieldForeign}}}
                </div>
            </div>
            <div class="cell form-group col-md-4"></div>
            <div class="cell form-group col-md-4" data-name="linkMultipleField">
                <label class="control-label" data-name="linkMultipleField">{{translate 'linkMultipleField' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="linkMultipleField">
                    {{{linkMultipleField}}}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="cell form-group col-md-4" data-name="auditedForeign">
                <label class="control-label" data-name="auditedForeign">{{translate 'audited' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="auditedForeign">
                    {{{auditedForeign}}}
                </div>
            </div>
            <div class="cell form-group col-md-4"></div>
            <div class="cell form-group col-md-4" data-name="audited">
                <label class="control-label" data-name="audited">{{translate 'audited' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="audited">
                    {{{audited}}}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="cell form-group col-md-4" data-name="layoutForeign">
                <label class="control-label" data-name="layoutForeign">{{translate 'layout' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="layoutForeign">
                    {{{layoutForeign}}}
                </div>
            </div>
            <div class="cell form-group col-md-4"></div>
            <div class="cell form-group col-md-4" data-name="layout">
                <label class="control-label" data-name="layout">{{translate 'layout' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="layout">
                    {{{layout}}}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="cell form-group col-md-4" data-name="selectFilterForeign">
                <label class="control-label" data-name="selectFilterForeign">{{translate 'selectFilter' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="selectFilterForeign">
                    {{{selectFilterForeign}}}
                </div>
            </div>
            <div class="cell form-group col-md-4"></div>
            <div class="cell form-group col-md-4" data-name="selectFilter">
                <label class="control-label" data-name="selectFilter">{{translate 'selectFilter' category='fields' scope='EntityManager'}}</label>
                <div class="field" data-name="selectFilter">
                    {{{selectFilter}}}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="cell form-group col-md-4"></div>
            <div class="cell form-group col-md-4" data-name="parentEntityTypeList">
                <label class="control-label" data-name="parentEntityTypeList">
                    {{translate 'parentEntityTypeList' category='fields' scope='EntityManager'}}
                </label>
                <div class="field" data-name="parentEntityTypeList">
                    {{{parentEntityTypeList}}}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="cell form-group col-md-4"></div>
            <div class="cell form-group col-md-4" data-name="foreignLinkEntityTypeList">
                <label class="control-label" data-name="foreignLinkEntityTypeList">
                    {{translate 'foreignLinkEntityTypeList' category='fields' scope='EntityManager'}}
                </label>
                <div class="field" data-name="foreignLinkEntityTypeList">
                    {{{foreignLinkEntityTypeList}}}
                </div>
            </div>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/layouts/rows.tpl
<div class="button-container">
    <div class="btn-group">
    {{#each buttonList}}
        {{button name label=label scope='Admin' style=style className='btn-xs-wide'}}
    {{/each}}
    </div>
</div>

<style>
    ul.enabled {
        li {
            &[data-hidden="true"] {
                .left {
                    color: var(--text-muted-color);
                }
            }
        }
    }


</style>

<div id="layout" class="row">
    <div class="col-sm-5">
        <div class="well enabled-well" tabindex="-1">
            <header>{{translate 'Enabled' scope='Admin'}}</header>
            <ul class="enabled connected">
                {{#each layout}}
                    <li
                        class="cell"
                        draggable="true"
                        {{#each ../dataAttributeList}}data-{{toDom this}}="{{prop ../this this}}" {{/each}}
                        title="{{labelText}}"
                    >
                        <div class="left" style="width: calc(100% - var(--17px));">
                            <span>{{labelText}}</span>
                        </div>
                        {{#if ../editable}}
                        {{#unless notEditable}}
                        <div class="right" style="width: 17px;"><a
                            role="button"
                            tabindex="0"
                            data-action="editItem"
                            class="edit-field"
                        ><i class="fas fa-pencil-alt fa-sm"></i></a></div>
                        {{/unless}}
                        {{/if}}
                    </li>
                {{/each}}
            </ul>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="well">
            <header>{{translate 'Disabled' scope='Admin'}}</header>
            <ul class="disabled connected">
                {{#each disabledFields}}
                    <li
                        class="cell"
                        draggable="true"
                        {{#each ../dataAttributeList}}data-{{toDom this}}="{{prop ../this this}}" {{/each}}
                        title="{{labelText}}"
                    >
                        <div class="left" style="width: calc(100% - var(--17px));">
                            <span>{{labelText}}</span>
                        </div>
                        {{#if ../editable}}
                        {{#unless notEditable}}
                        <div class="right" style="width: 17px;"><a
                            role="button"
                            tabindex="0"
                            data-action="editItem"
                            class="edit-field"
                        ><i class="fas fa-pencil-alt fa-sm"></i></a></div>
                        {{/unless}}
                        {{/if}}
                    </li>
                {{/each}}
            </ul>
        </div>
    </div>
</div>


_delimiter_6nomlrqbt5x
res/templates/admin/layouts/index.tpl
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

_delimiter_6nomlrqbt5x
res/templates/admin/layouts/grid.tpl
<div class="button-container">
    <div class="btn-group">
    {{#each buttonList}}
        {{button name label=label scope='Admin' style=style className='btn-xs-wide'}}
    {{/each}}
    </div>
</div>

<div id="layout" class="row">
    <div class="col-md-8">
        <div class="well enabled-well" tabindex="-1">
            <header>{{translate 'Layout' scope='LayoutManager'}}</header>
            <ul class="panels">
                {{#each panelDataList}}
                    <li data-number="{{number}}" class="panel-layout" data-tab-break="{{tabBreak}}">
                        {{{var viewKey ../this}}}
                    </li>
                {{/each}}
            </ul>

            <div><a role="button" tabindex="0" data-action="addPanel">{{translate 'Add Panel' scope='Admin'}}</a></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="well">
            <header>{{translate 'Available Fields' scope='Admin'}}</header>
            <ul class="disabled cells clearfix">
                {{#each disabledFields}}
                <li class="cell" data-name="{{./this}}" title="{{translate this scope=../scope category='fields'}}">
                    <div class="left" style="width: calc(100% - 14px);">
                        {{translate this scope=../scope category='fields'}}
                    </div>
                    <div class="right" style="width: 14px;">
                        <a
                            role="button"
                            tabindex="0"
                            data-action="removeField"
                            class="remove-field"
                        ><i class="fas fa-times"></i></a>
                    </div>
                </li>
                {{/each}}
            </ul>
        </div>
    </div>
</div>

<div id="layout-row-tpl" style="display: none;">
    <li data-cell-count="{{columnCount}}">
        <div class="row-actions clear-fix">
            <a role="button" tabindex="0" data-action="removeRow" class="remove-row"><i class="fas fa-times"></i></a>
            <a role="button" tabindex="0" data-action="plusCell" class="add-cell"><i class="fas fa-plus"></i></a>
        </div>
        <ul class="cells" data-cell-count="{{columnCount}}">
            <% for (var i = 0; i < {{columnCount}}; i++) { %>
                <li class="empty cell">
                <div class="right" style="width: 14px;">
                    <a
                        role="button"
                        tabindex="0"
                        data-action="minusCell"
                        class="remove-field"
                    ><i class="fas fa-minus"></i></a>
                </div>
                </li>
            <% } %>
        </ul>
    </li>
</div>

<div id="empty-cell-tpl" style="display: none;">
    <li class="empty cell disabled">
        <div class="right" style="width: 14px;">
            <a
                role="button"
                tabindex="0"
                data-action="minusCell"
                class="remove-field"
            ><i class="fas fa-minus"></i></a>
        </div>
    </li>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/layouts/grid-panel.tpl
<header data-name="{{name}}">
    <a
        role="button"
        tabindex="0"
        data-action="edit-panel-label"
        class="edit-panel-label"
    ><i class="fas fa-pencil-alt fa-sm"></i></a>
    <label
        data-is-custom="{{#if isCustomLabel}}true{{/if}}"
        data-label="{{label}}"
        class="panel-label"
    >{{labelTranslated}}</label>&nbsp;
    <a
        role="button"
        tabindex="0"
        style="float: right;"
        data-action="removePanel"
        class="remove-panel"
        data-number="{{number}}"
    ><i class="fas fa-times"></i></a>
</header>
<ul class="rows">
{{#each rows}}
    <li data-cell-count="{{./this.length}}">
        <div class="row-actions clear-fix">
            <a
                role="button"
                tabindex="0"
                data-action="removeRow"
                class="remove-row"
            ><i class="fas fa-times"></i></a>
            <a
                role="button"
                tabindex="0"
                data-action="plusCell"
                class="add-cell"
            ><i class="fas fa-plus"></i></a>
        </div>
        <ul class="cells" data-cell-count="{{./this.length}}">
        {{#each this}}
            {{#if this}}
            <li
                class="cell"
                data-name="{{name}}"
                {{#if hasCustomLabel}}
                data-custom-label="{{customLabel}}"
                {{/if}}
                data-no-label="{{noLabel}}"
                title="{{label}}"
            >
                <div class="left" style="width: calc(100% - 14px);">{{label}}</div>
                <div class="right" style="width: 14px;">
                    <a
                        role="button"
                        tabindex="0"
                        data-action="removeField"
                        class="remove-field"
                    ><i class="fas fa-times"></i></a>
                </div>
            </li>
            {{else}}
            <li class="empty cell">
                <div class="right" style="width: 14px;">
                    <a
                        role="button"
                        tabindex="0"
                        data-action="minusCell"
                        class="remove-field"
                    ><i class="fas fa-minus"></i></a>
                </div>
            </li>
            {{/if}}
        {{/each}}
        </ul>
    </li>
{{/each}}
</ul>
<div>
    <a
        role="button"
        tabindex="0"
        data-action="addRow"
    ><i class="fas fa-plus"></i></a>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/layouts/record/edit-attributes.tpl
{{#each attributeDataList}}
<div class="row">
    <div
        class="cell form-group {{#if isWide}}col-md-12{{else}}col-md-6{{/if}}"
        data-name="{{name}}"
    >
        <label
            class="control-label"
            data-name="{{name}}"
        >{{label}}</label>
        <div
            class="field"
            data-name="{{name}}"
        >{{{var viewKey ../this}}}</div>
    </div>
</div>
{{/each}}

_delimiter_6nomlrqbt5x
res/templates/admin/label-manager/index.tpl
<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span></span></span>
{{translate 'Label Manager' scope='Admin'}}</h3></div>

<div class="row">
    <div class="col-sm-3">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="cell">
                    <div class="field">
                        <select data-name="language" class="form-control">
                            {{#each languageList}}
                            <option
                                value="{{this}}"
                                {{#ifEqual this ../language}} selected{{/ifEqual}}
                            >{{translateOption this field='language'}}</option>
                            {{/each}}
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-body">
                <ul class="list-unstyled" style="overflow-x: hidden;">
                {{#each scopeList}}
                    <li>
                        <button
                            class="btn btn-link"
                            data-name="{{./this}}"
                            data-action="selectScope"
                        >{{translate this category='scopeNames'}}</button>
                    </li>
                {{/each}}
                </ul>
            </div>
        </div>
    </div>

    <div class="col-sm-9">
        <div class="language-record">
            {{{record}}}
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/label-manager/edit.tpl
<div class="page-header">
    <h4>{{translate scope category='scopeNames'}}</h4>
</div>

{{#unless categoryList.length}}
    {{translate 'No Data'}}
{{else}}
    <div class="button-container">
        <div class="btn-group">
            <button class="btn btn-primary btn-xs-wide" data-action="save">{{translate 'Save'}}</button>
            <button class="btn btn-default btn-xs-wide" data-action="cancel">{{translate 'Cancel'}}</button>
        </div>
    </div>

    <div class="button-container negate-no-side-margin">
        <input
            type="text"
            maxlength="64"
            placeholder="{{translate 'Search'}}"
            data-name="quick-search"
            class="form-control"
            spellcheck="false"
        >
    </div>
{{/unless}}

{{#each categoryList}}
<div class="panel panel-default category-panel" data-name="{{./this}}" style="overflow: hidden;">
    <div class="panel-heading clearfix">
        <div
            class="pull-left"
            style="
                margin-right: 10px;
                padding-top: calc((var(--panel-heading-height) - var(--panel-heading-font-size)) / 2 - 1px);
            "
        >
            <a
                role="button"
                tabindex="0"
                data-action="showCategory"
                data-name="{{./this}}"
                class="action"
            ><span class="fas fa-chevron-down"></span></a>
            <a
                role="button"
                tabindex="0"
                data-action="hideCategory"
                data-name="{{./this}}"
                class="hidden action"
            ><span class="fas fa-chevron-up"></span></a>
        </div>
        <h4 class="panel-title">
            <span class="action" style="cursor: pointer;" data-action="toggleCategory" data-name="{{./this}}">
            {{translate this}}
            </span>
        </h4>
    </div>
    <div class="panel-body hidden" data-name="{{./this}}">{{{var this ../this}}}</div>
</div>
{{/each}}
<div class="no-data hidden">{{translate 'No Data'}}</div>

_delimiter_6nomlrqbt5x
res/templates/admin/label-manager/category.tpl
{{#each categoryDataList}}
<div class="row" data-name="{{name}}">
    <div class="cell col-md-5 form-group">
        <div class="field detail-field-container">{{label}}</div>
    </div>
    <div class="cell col-md-7 form-group" data-name="{{name}}">
        <div class="field">
            <input type="input" class="form-control label-value" value="{{value}}" data-name="{{name}}">
        </div>
    </div>
</div>
{{/each}}

_delimiter_6nomlrqbt5x
res/templates/admin/integrations/oauth2.tpl
<div class="button-container">
    <div class="btn-group">
        <button class="btn btn-primary btn-xs-wide" data-action="save">{{translate 'Save'}}</button>
        <button class="btn btn-default btn-xs-wide" data-action="cancel">{{translate 'Cancel'}}</button>
    </div>
</div>

<div class="row">
    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-body panel-body-form">
                <div class="cell form-group" data-name="enabled">
                    <label
                        class="control-label"
                        data-name="enabled"
                    >{{translate 'enabled' scope='Integration' category='fields'}}</label>
                    <div class="field" data-name="enabled">{{{enabled}}}</div>
                </div>
                {{#each dataFieldList}}
                    <div class="cell form-group" data-name="{{./this}}">
                        <label
                            class="control-label"
                            data-name="{{./this}}"
                        >{{translate this scope='Integration' category='fields'}}</label>
                        <div class="field" data-name="{{./this}}">{{{var this ../this}}}</div>
                    </div>
                {{/each}}
                <div class="cell form-group" data-name="redirectUri">
                    <label
                        class="control-label"
                        data-name="redirectUri"
                    >{{translate 'redirectUri' scope='Integration' category='fields'}}</label>
                    <div class="field" data-name="redirectUri">
                        <input type="text" class="form-control" readonly value="{{redirectUri}}">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        {{#if helpText}}
        <div class="well">
            {{{helpText}}}
        </div>
        {{/if}}
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/integrations/index.tpl
<div class="page-header">
    <h3>
    <a href="#Admin">{{translate 'Administration'}}</a>
    <span class="breadcrumb-separator"><span></span></span>
    {{translate 'Integrations' scope='Admin'}}
    </h3>
</div>

<div class="row">
    <div id="integrations-menu" class="col-sm-3">
        <ul class="list-group list-group-panel">
        {{#each integrationDataList}}
            <li
                class="list-group-item"
            ><a
                role="button"
                tabindex="0"
                class="integration-link {{#if active}} disabled text-muted {{/if}}"
                data-name="{{name}}"
            >{{{translate name scope='Integration' category='titles'}}}</a></li>
        {{/each}}
        </ul>
    </div>
    <div id="integration-panel" class="col-sm-9">
        <h4 id="integration-header" style="margin-top: 0px;"></h4>
        <div id="integration-content">
            {{{content}}}
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/integrations/edit.tpl
<div class="button-container">
    <div class="btn-group">
        <button class="btn btn-primary btn-xs-wide" data-action="save">{{translate 'Save'}}</button>
        <button class="btn btn-default btn-xs-wide" data-action="cancel">{{translate 'Cancel'}}</button>
    </div>
</div>

<div class="row">
    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-body panel-body-form">
                <div class="cell form-group" data-name="enabled">
                    <label
                        class="control-label"
                        data-name="enabled"
                    >{{translate 'enabled' scope='Integration' category='fields'}}</label>
                    <div class="field" data-name="enabled">{{{enabled}}}</div>
                </div>
                {{#each fieldDataList}}
                    <div
                        class="cell form-group"
                        data-name="{{name}}"
                    >
                        <label
                            class="control-label"
                            data-name="{{name}}"
                        >{{label}}</label>
                        <div
                            class="field"
                            data-name="{{name}}"
                        >{{{var name ../this}}}</div>
                    </div>
                {{/each}}
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        {{#if helpText}}
        <div class="well">
            {{complexText helpText}}
        </div>
        {{/if}}
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/formula-sandbox/index.tpl
<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span></span></span>
{{translate 'Formula Sandbox' scope='Admin'}}</h3></div>


<div class="record">{{{record}}}</div>

_delimiter_6nomlrqbt5x
res/templates/admin/formula/modals/add-function.tpl
<div class="complex-text margin-bottom-2x">{{{text}}}</div>

<ul class="list-group no-side-margin array-add-list-group">
    {{#each functionDataList}}
        <li class="list-group-item clearfix">
            <button class="btn btn-default pull-right btn-sm btn-icon" data-action="add" data-value="{{insertText}}">
                <span class="fas fa-plus"></span>
            </button>
            {{insertText}}
        </li>
    {{/each}}
</ul>
_delimiter_6nomlrqbt5x
res/templates/admin/field-manager/list.tpl
<div class="button-container">
    <div class="btn-group">
        {{#if hasAddField}}
        <button
            type="button"
            class="btn btn-default btn-wide"
            data-action="addField"
        ><span class="fas fa-plus fa-sm"></span><span>{{translate 'Add Field' scope='Admin'}}</span></button>
        {{/if}}
    </div>
</div>

<div class="margin-bottom-2x margin-top">
    <input
        type="text"
        maxlength="64"
        placeholder="{{translate 'Search'}}"
        data-name="quick-search"
        class="form-control"
        spellcheck="false"
    >
</div>

<table class="table fields-table table-panel table-hover">
    <thead>
        <th style="width: 35%">{{translate 'Label' scope='FieldManager'}}</th>
        <th style="width: 35%">{{translate 'Name' scope='FieldManager'}}</th>
        <th style="width: 20%">{{translate 'Type' scope='FieldManager'}}</th>
        <th style="width: 8%; text-align: right;"></th>
    </thead>
    <tbody>
    {{#each fieldDefsArray}}
    <tr data-name="{{name}}" class="field-row">
        <td>
            {{#if isEditable}}
                <a
                    href="#Admin/fieldManager/scope={{../scope}}&field={{name}}"
                    class="field-link"
                    data-scope="{{../scope}}"
                    data-field="{{name}}"
                >{{translate name scope=../scope category='fields'}}</a>
            {{else}}
                {{translate name scope=../scope category='fields'}}
            {{/if}}
        </td>
        <td>
            <span class="{{#if isCustom}} text-info {{/if}}">{{name}}</span>

        </td>
        <td>{{translate type category='fieldTypes' scope='Admin'}}</td>
        <td style="text-align: right">
            <div class="btn-group row-dropdown-group">
                <button
                    class="btn btn-link btn-sm dropdown-toggle"
                    data-toggle="dropdown"
                ><span class="caret"></span></button>
                <ul class="dropdown-menu pull-right">
                    <li>
                        <a
                            role="button"
                            tabindex="0"
                            data-action="viewDetails"
                            data-name="{{name}}"
                        >{{translate 'View Details' scope='FieldManager'}}</a>
                    </li>
                    {{#if isCustom}}
                        <li class="divider"></li>
                        <li>
                            <a
                                role="button"
                                tabindex="0"
                                data-action="removeField"
                                data-name="{{name}}"
                            >{{translate 'Remove'}}</a>
                        </li>
                    {{/if}}
                </ul>
            </div>
        </td>
    </tr>
    {{/each}}
    </tbody>
</table>

<div class="no-data hidden">{{translate 'No Data'}}</div>

_delimiter_6nomlrqbt5x
res/templates/admin/field-manager/index.tpl
<div class="page-header">
    {{{header}}}
</div>

<div class="row">
    <div id="fields-panel" class="col-sm-9">
        <div id="fields-content">
            {{{content}}}
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/field-manager/header.tpl
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

_delimiter_6nomlrqbt5x
res/templates/admin/field-manager/edit.tpl
<div class="button-container">
    <div class="btn-group">
    <button class="btn btn-primary btn-xs-wide" data-action="save">{{translate 'Save'}}</button>
    <button class="btn btn-default btn-xs-wide" data-action="close">{{translate 'Close'}}</button>
    {{#if hasResetToDefault}}
    <button
        class="btn btn-default"
        data-action="resetToDefault"
    >{{translate 'Reset to Default' scope='Admin'}}</button>
    {{/if}}
    </div>
</div>

<div class="row middle">
    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-body panel-body-form">
                <div class="cell form-group" data-name="type">
                    <label class="control-label" data-name="type">{{translate 'type' scope='Admin' category='fields'}}</label>
                    <div class="field" data-name="type">{{translate type scope='Admin' category='fieldTypes'}}</div>
                </div>
                <div class="cell form-group" data-name="name">
                    <label class="control-label" data-name="name">{{translate 'name' scope='Admin' category='fields'}}</label>
                    <div class="field" data-name="name">{{{name}}}</div>
                </div>
                <div class="cell form-group" data-name="label">
                    <label class="control-label" data-name="label">{{translate 'label' scope='Admin' category='fields'}}</label>
                    <div class="field" data-name="label">{{{label}}}</div>
                </div>
                {{#each paramDataList}}
                    {{#unless hidden}}
                    <div class="cell form-group" data-name="{{name}}">
                        <label class="control-label" data-name="{{name}}">{{label}}</label>
                        <div class="field" data-name="{{name}}">{{{var name ../this}}}</div>
                    </div>
                    {{/unless}}
                {{/each}}
        </div>
    </div>

    {{#if hasDynamicLogicPanel}}
    <div class="panel panel-default">
        <div class="panel-heading"><h4 class="panel-title">{{translate 'Dynamic Logic' scope='FieldManager'}}</h4></div>
            <div class="panel-body panel-body-form">
                {{#if dynamicLogicVisible}}
                <div class="cell form-group" data-name="dynamicLogicVisible">
                    <label class="control-label" data-name="dynamicLogicVisible">{{translate 'dynamicLogicVisible' scope='Admin' category='fields'}}</label>
                    <div class="field" data-name="dynamicLogicVisible">{{{dynamicLogicVisible}}}</div>
                </div>
                {{/if}}
                {{#if dynamicLogicRequired}}
                <div class="cell form-group" data-name="dynamicLogicRequired">
                    <label class="control-label" data-name="dynamicLogicRequired">{{translate 'dynamicLogicRequired' scope='Admin' category='fields'}}</label>
                    <div class="field" data-name="dynamicLogicRequired">{{{dynamicLogicRequired}}}</div>
                </div>
                {{/if}}
                {{#if dynamicLogicReadOnly}}
                <div class="cell form-group" data-name="dynamicLogicReadOnly">
                    <label class="control-label" data-name="dynamicLogicReadOnly">{{translate 'dynamicLogicReadOnly' scope='Admin' category='fields'}}</label>
                    <div class="field" data-name="dynamicLogicReadOnly">{{{dynamicLogicReadOnly}}}</div>
                </div>
                {{/if}}
                {{#if dynamicLogicOptions}}
                <div class="cell form-group" data-name="dynamicLogicOptions">
                    <label class="control-label" data-name="dynamicLogicOptions">{{translate 'dynamicLogicOptions' scope='Admin' category='fields'}}</label>
                    <div class="field" data-name="dynamicLogicOptions">{{{dynamicLogicOptions}}}</div>
                </div>
                {{/if}}
                {{#if dynamicLogicInvalid}}
                <div class="cell form-group" data-name="dynamicLogicInvalid">
                    <label class="control-label" data-name="dynamicLogicInvalid">{{translate 'dynamicLogicInvalid' scope='Admin' category='fields'}}</label>
                    <div class="field" data-name="dynamicLogicInvalid">{{{dynamicLogicInvalid}}}</div>
                </div>
                {{/if}}
                {{#if dynamicLogicReadOnlySaved}}
                    <div class="cell form-group" data-name="dynamicLogicReadOnlySaved">
                        <label class="control-label" data-name="dynamicLogicReadOnlySaved">{{translate 'dynamicLogicReadOnlySaved' scope='Admin' category='fields'}}</label>
                        <div class="field" data-name="dynamicLogicReadOnlySaved">{{{dynamicLogicReadOnlySaved}}}</div>
                    </div>
                {{/if}}
            </div>
        </div>
    </div>
    {{/if}}
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/field-manager/modals/add-field.tpl
<div class="margin-bottom-2x margin-top">
    <input
        type="text"
        maxlength="64"
        placeholder="{{translate 'Search'}}"
        data-name="quick-search"
        class="form-control"
        spellcheck="false"
    >
</div>

<ul class="list-group no-side-margin">
{{#each typeList}}
    <li class="list-group-item" data-name="{{./this}}">
        <a role="button" tabindex="0" data-action="addField" data-type="{{./this}}" class="text-bold">
        {{translate this category='fieldTypes' scope='Admin'}}
        </a>
        <a role="button" tabindex="0" class="text-muted pull-right info" data-name="{{./this}}">
            <span class="fas fa-info-circle"></span>
        </a>
    </li>
{{/each}}
</ul>

<div class="no-data hidden">{{translate 'No Data'}}</div>

_delimiter_6nomlrqbt5x
res/templates/admin/field-manager/fields/dynamic-logic-options/edit.tpl

<div class="dynamic-logic-options">
    <div class="dynamic-logic-options-list-container list-group">
        {{#each itemDataList}}
        <div class="list-group-item">
            <div class="clearfix option-list-item-header">
                <div class="pull-right">
                    <a
                        role="button"
                        tabindex="0"
                        data-action="removeOptionList"
                        data-index="{{index}}"
                        class="remove-option-list"
                        title="{{translate 'Remove'}}"
                    >
                        <span class="fas fa-minus fa-sm"></span>
                    </a>
                </div>
            </div>
            <div>
                <div class="options-container" data-key="{{optionsViewKey}}">
                    {{{var optionsViewKey ../this}}}
                </div>
            </div>
            <div>
                <div class="pull-right">
                    <a
                        role="button"
                        tabindex="0"
                        data-action="editConditions"
                        data-index="{{index}}"
                    >{{translate 'Edit'}}</a>
                </div>
                <div class="string-container" data-key="{{conditionGroupViewKey}}">
                    {{{var conditionGroupViewKey ../this}}}
                </div>
            </div>
        </div>
        {{/each}}
    </div>
    <div>
        <a
            role="button"
            tabindex="0"
            data-action="addOptionList"
            title="{{translate 'Add'}}"
            class="add-option-list"
        ><span class="fas fa-plus fa-sm"></span></a>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/field-manager/fields/dynamic-logic-conditions/edit.tpl
<div>
    <div class="pull-right">
        <a role="button" tabindex="0" data-action="editConditions">{{translate 'Edit'}}</a>
    </div>
    <div class="top-group-string-container">
        {{{conditionGroup}}}
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/field-manager/fields/dynamic-logic-conditions/detail.tpl
{{#if isNotEmpty}}
    <div>
        <div class="top-group-string-container">
            {{{conditionGroup}}}
        </div>
    </div>
{{else}}
    {{#if isSet}}
        <span class="none-value">{{translate 'None'}}</span>
    {{else}}
        <span class="loading-value"></span>
    {{/if}}
{{/if}}

_delimiter_6nomlrqbt5x
res/templates/admin/extensions/ready.tpl

<p class="text-danger">
    {{{text}}}
</p>


_delimiter_6nomlrqbt5x
res/templates/admin/extensions/index.tpl
<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span></span></span>
{{translate 'Extensions' scope='Admin'}}</h3></div>

<div class="panel panel-default upload">
    <div class="panel-heading">
        <h4 class="panel-title">{{translate 'selectExtensionPackage' category='messages' scope='Admin'}}</h4>
    </div>
    <div class="panel-body">
        <div>
            <input type="file" name="package" accept="application/zip">
        </div>
        <div class="message-container text-danger" style="height: 20px; margin-bottom: 10px; margin-top: 10px;"></div>
        <div class="buttons-container">
            <button
                class="btn btn-primary disabled"
                data-action="upload"
                disabled="disabled"
            >{{translate 'Upload' scope='Admin'}}</button>
        </div>
    </div>
</div>

<p class="text-danger notify-text hidden"></p>

<div class="list-container">{{{list}}}</div>


_delimiter_6nomlrqbt5x
res/templates/admin/extensions/done.tpl

<p class="text-success">
    {{{text}}}
</p>


_delimiter_6nomlrqbt5x
res/templates/admin/entity-manager/scope.tpl
<div class="page-header">
    <h3><a href="#Admin">{{translate 'Administration'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        <a href="#Admin/entityManager">{{translate 'Entity Manager' scope='Admin'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        {{translate scope category='scopeNames'}}
    </h3>
</div>

<div class="button-container">
    <div class="btn-group actions-btn-group" role="group">
        {{#if isEditable}}
        <button class="btn btn-default action btn-lg action btn-wide" data-action="editEntity">
            <span class="icon fas fa-cog"></span>
            {{translate 'Edit'}}
        </button>
        {{/if}}
        {{#if isRemovable}}
        <button class="btn btn-default btn-lg dropdown-toggle item-dropdown-button" data-toggle="dropdown">
            <span class="fas fa-ellipsis-h"></span>
        </button>
        <ul class="dropdown-menu pull-left">
            <li><a role="button" tabindex="0" data-action="removeEntity">{{translate 'Remove'}}</a></li>
        </ul>
        {{/if}}
    </div>
</div>

<div class="record record-container">{{{record}}}</div>
<div class="record">
    <div class="record-grid">
        <div class="left">
            <div class="panel panel-default">
                <div class="panel-body panel-body-form">
                    <div class="row">
                        <div class="cell col-sm-6 form-group">
                            {{#if hasFields}}
                            <div>
                                <a
                                    class="btn btn-default btn-lg action btn-full-wide"
                                    href="#Admin/fieldManager/scope={{scope}}"
                                >
                                    <span class="fas fa-asterisk"></span>
                                    {{translate 'Fields' scope='EntityManager'}}
                                </a>
                            </div>
                            {{/if}}
                        </div>
                        <div class="cell col-sm-6 form-group">
                            {{#if hasRelationships}}
                            <div>
                                <a
                                    class="btn btn-default btn-lg action btn-full-wide"
                                    href="#Admin/linkManager/scope={{scope}}"
                                >
                                    <span class="fas fa-link"></span>
                                    {{translate 'Relationships' scope='EntityManager'}}
                                </a>
                            </div>
                            {{/if}}
                        </div>
                        <div class="cell col-sm-6 form-group">
                            {{#if hasLayouts}}
                            <div>
                                <a
                                    class="btn btn-default btn-lg action btn-full-wide"
                                    href="#Admin/layouts/scope={{scope}}&em=true"
                                >
                                    <span class="fas fa-table"></span>
                                    {{translate 'Layouts' scope='EntityManager'}}
                                </a>
                            </div>
                            {{/if}}
                        </div>
                        <div class="cell col-sm-6 form-group">
                            {{#if hasFormula}}
                                <div>
                                    <a
                                        class="btn btn-default btn-lg action btn-full-wide"
                                        data-action="editFormula"
                                    >
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

_delimiter_6nomlrqbt5x
res/templates/admin/entity-manager/index.tpl
<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span></span></span>
{{translate 'Entity Manager' scope='Admin'}}</h3></div>

<div class="button-container">
    <div class="btn-group">
        <button class="btn btn-default" data-action="createEntity">
            <span class="fas fa-plus fa-sm"></span><span>{{translate 'Create Entity' scope='Admin'}}</span>
        </button>
        <button
            class="btn btn-default dropdown-toggle"
            data-toggle="dropdown"
        ><span class="fas fa-ellipsis-h"></span></button>
        <ul class="dropdown-menu pull-right">
            <li>
                <a
                    role="button"
                    data-action="export"
                    tabindex="0"
                >{{translate 'Export'}}</a>
            </li>
        </ul>
    </div>
</div>

<div class="row">
<div class="col-md-11">

<div class="margin-bottom-2x margin-top">
    <input
        type="text"
        maxlength="64"
        placeholder="{{translate 'Search'}}"
        data-name="quick-search"
        class="form-control"
        spellcheck="false"
    >
</div>
<table class="table table-hover table-panel scopes-table">
    <thead>
        <tr>
            <th>{{translate 'label' scope='EntityManager' category='fields'}}</th>
            <th style="width: 27%">{{translate 'name' scope='EntityManager' category='fields'}}</th>
            <th style="width: 19%">{{translate 'type' scope='EntityManager' category='fields'}}</th>
            <th style="width: 19%">{{translate 'module' scope='EntityManager' category='fields'}}</th>
        </tr>
    </thead>
    <tbody>
    {{#each scopeDataList}}
        <tr data-scope="{{name}}" class="scope-row">
            <td>
                {{#if hasView}}
                <a href="#Admin/entityManager/scope={{name}}">{{label}}</a>
                {{else}}
                {{label}}
                {{/if}}
            </td>
            <td>
                {{name}}
            </td>
            <td>
                {{#if type}}
                {{translateOption type field='type' scope='EntityManager'}}
                {{/if}}
            </td>
            <td>
                {{#if module}}
                    {{translateOption module field='module' scope='EntityManager'}}
                {{/if}}
            </td>
        </tr>
    {{/each}}
    </tbody>
</table>
<div class="no-data hidden">{{translate 'No Data'}}</div>
</div>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/entity-manager/formula.tpl
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

_delimiter_6nomlrqbt5x
res/templates/admin/entity-manager/edit.tpl
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

_delimiter_6nomlrqbt5x
res/templates/admin/entity-manager/record/edit-formula.tpl
<div class="row">
    <div data-name="{{field}}" class="cell col-sm-12">
        <label class="control-label" data-name="{{field}}">
            {{translate field category='fields' scope='EntityManager'}}
        </label>
        <div class="field" data-name="{{field}}">
            {{{var fieldKey this}}}
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/entity-manager/modals/select-icon.tpl
<div class="margin-top margin-bottom-2x">
	<a role="button" tabindex="0" data-action="select" class="action btn btn-default" data-value="" style="cursor: pointer;">
		{{translate 'None'}}
	</a>
</div>

<div class="margin-top margin-bottom-2x">
    <input class="form-control" type="input" data-name="quick-search" placeholder="{{translate 'Search'}}">
</div>

<div class="row icons">
{{#each iconDataList}}
	{{#each this}}
	<div
        class="col-md-2 col-sm-2 icon-container"
        style="height: 6em; text-align: center; overflow: hidden;"
        data-name="{{./this}}"
    >
		<span data-action="select" class="action" data-value="{{./this}}" style="cursor: pointer;">
			<div style="text-align: center; height: 1.5em;">
				<span class="{{./this}}"></span>
			</div>
			<div style="text-align: center;">
				<span>{{./this}}</span>
			</div>
		</span>
	</div>
	{{/each}}
{{/each}}
</div>

_delimiter_6nomlrqbt5x
res/templates/admin/entity-manager/fields/icon-class/edit.tpl
<div>
    <button
        class="btn btn-default pull-right btn-icon"
        data-action="selectIcon"
        title="{{translate 'Select'}}"
    ><span class="fas fa-angle-up"></span></button>
    <span style="vertical-align: middle;">
        {{#if value}}
        <span class="{{value}}"></span>
        {{else}}
        {{translate 'None'}}
        {{/if}}
    </span>
</div>
_delimiter_6nomlrqbt5x
res/templates/admin/dynamic-logic/modals/edit.tpl
<div class="panel panel-default no-side-margin"><div class="panel-body">

<div class="top-group-container dynamic-logic-expression-container">{{{conditionGroup}}}</div>

</div></div>

_delimiter_6nomlrqbt5x
res/templates/admin/dynamic-logic/conditions-string/item-operator-only-date.tpl
{{translate field category='fields' scope=scope}} {{{operatorString}}} {{translateOption dateValue field='dateSearchRanges'}}
_delimiter_6nomlrqbt5x
res/templates/admin/dynamic-logic/conditions-string/item-operator-only-base.tpl
{{translate field category='fields' scope=scope}} {{{operatorString}}}
_delimiter_6nomlrqbt5x
res/templates/admin/dynamic-logic/conditions-string/item-multiple-values-base.tpl
{{translate field category='fields' scope=scope}} {{{operatorString}}}
({{#each valueViewDataList}}<span data-name="{{key}}">{{{var key ../this}}}</span>{{#unless isEnd}}, {{/unless}}{{/each}})
_delimiter_6nomlrqbt5x
res/templates/admin/dynamic-logic/conditions-string/item-base.tpl
{{leftString}} {{{operatorString}}} <span data-name="{{valueViewKey}}">{{{value}}}</span>

_delimiter_6nomlrqbt5x
res/templates/admin/dynamic-logic/conditions-string/group-not.tpl

    <div>{{translate 'not' category='logicalOperators' scope='Admin'}} (
        <div data-view-key="{{viewKey}}" style="margin-left: 15px;">{{{var viewKey this}}}</div>
    )</div>

_delimiter_6nomlrqbt5x
res/templates/admin/dynamic-logic/conditions-string/group-base.tpl
{{#if isEmpty}}
    {{translate 'None'}}
{{else}}
    <div>(
    {{#each viewDataList}}
        <div data-view-key="{{key}}" style="margin-left: 15px;">{{{var key ../this}}}</div>
        {{#unless isEnd}}
        <div style="margin-left: 15px;">
            {{translate ../operator category='logicalOperators' scope='Admin'}}
        </div>
        {{/unless}}
    {{/each}}
    )</div>
{{/if}}
_delimiter_6nomlrqbt5x
res/templates/admin/dynamic-logic/conditions/not.tpl

<div class="group-head" data-level="{{level}}">
    <a class="pull-right" role="button" data-action="remove"><span class="fas fa-times"></span></a>
    <div><span class="not-operator">{{translate 'not' category='logicalOperators' scope='Admin'}}</span> (</div>
</div>

<div class="item-list" data-level="{{level}}">
    <div data-view-key="{{viewKey}}">{{#if hasItem}}{{{var viewKey this}}}{{/if}}</div>
</div>

<div class="group-bottom" data-level="{{level}}">
    <div class="btn-group">
        <a class="dropdown-toggle small" role="button" data-toggle="dropdown"><span class="fas fa-plus"></span></a>
        <ul class="dropdown-menu">
            <li><a
                    role="button"
                    tabindex="0"
                    data-action="addField"
                >{{translate 'Field' scope='DynamicLogic'}}</a></li>
            <li class="divider"></li>
            <li><a
                    role="button"
                    tabindex="0"
                    data-action="addAnd"
                >(... {{translate 'and' category='logicalOperators' scope='Admin'}} ...)</a></li>
            <li><a
                    role="button"
                    tabindex="0"
                    data-action="addOr"
                >(... {{translate 'or' category='logicalOperators' scope='Admin'}} ...)</a></li>
            <li><a
                    role="button"
                    tabindex="0"
                    data-action="addNot"
                >{{translate 'not' category='logicalOperators' scope='Admin'}} (...)</a></li>
            <li class="divider"></li>
            <li><a
                role="button"
                tabindex="0"
                data-action="addCurrentUser"
            >${{translate 'User' scope='scopeNames'}}</a></li>
            <li><a
                role="button"
                tabindex="0"
                data-action="addCurrentUserTeams"
            >${{translate 'User' scope='scopeNames'}}.{{translate 'teams' category='fields' scope='User'}}</a></li>
        </ul>
    </div>
</div>

<div>)</div>

_delimiter_6nomlrqbt5x
res/templates/admin/dynamic-logic/conditions/group-base.tpl

<div class="group-head" data-level="{{level}}">
    {{#ifNotEqual level 0}}
    <a class="pull-right" role="button" tabindex="0" data-action="remove"><span class="fas fa-times"></span></a>
    {{/ifNotEqual}}
    {{#ifNotEqual level 0}}
    <div>(</div>
    {{else}}
    &nbsp;
    {{/ifNotEqual}}
</div>

<div class="item-list" data-level="{{level}}">
{{#each viewDataList}}
    <div data-view-key="{{key}}">{{{var key ../this}}}</div>
    <div class="group-operator" data-view-ref-key="{{key}}">{{translate ../groupOperator category='logicalOperators' scope='Admin'}}</div>
{{/each}}
</div>

<div class="group-bottom" data-level="{{level}}">
    <div class="btn-group">
        <a
            class="dropdown-toggle small"
            role="button"
            tabindex="0"
            data-toggle="dropdown"
        >{{translate groupOperator category='logicalOperators' scope='Admin'}} <span class="fas fa-plus"></span></a>
        <ul class="dropdown-menu">
            <li><a
                    role="button"
                    tabindex="0"
                    data-action="addField"
                >{{translate 'Field' scope='DynamicLogic'}}</a></li>
            <li class="divider"></li>
            <li><a
                    role="button"
                    tabindex="0"
                    data-action="addAnd"
                >(... {{translate 'and' category='logicalOperators' scope='Admin'}} ...)</a></li>
            <li><a
                    role="button"
                    tabindex="0"
                    data-action="addOr"
                >(... {{translate 'or' category='logicalOperators' scope='Admin'}} ...)</a></li>
            <li><a
                    role="button"
                    tabindex="0"
                    data-action="addNot"
                >{{translate 'not' category='logicalOperators' scope='Admin'}} (...)</a></li>
            <li class="divider"></li>
            <li><a
                role="button"
                tabindex="0"
                data-action="addCurrentUser"
            >${{translate 'User' scope='scopeNames'}}</a></li>
            <li><a
                role="button"
                tabindex="0"
                data-action="addCurrentUserTeams"
            >${{translate 'User' scope='scopeNames'}}.{{translate 'teams' category='fields' scope='User'}}</a></li>
        </ul>
    </div>
</div>

{{#ifNotEqual level 0}}
<div>)</div>
{{/ifNotEqual}}

_delimiter_6nomlrqbt5x
res/templates/admin/dynamic-logic/conditions/field-types/base.tpl
<div class="row dynamic-logic-edit-item-row">
    <div class="col-sm-2">{{leftString}}</div>
    <div class="col-sm-3">
        <select data-name="type" class="form-control">{{{options typeList type scope='DynamicLogic' field='operators'}}}</select>
    </div>
    <div class="col-sm-4 value-container">{{{value}}}</div>
    <div class="col-sm-3">
        <a class="pull-right" role="button" tabindex="0" data-action="remove"><span class="fas fa-times"></span></a>
        <span>{{translate operator category='logicalOperators' scope='Admin'}}</span>
    </div>
</div>

_delimiter_6nomlrqbt5x
modules/crm/res/templates/target-list/record/panels/opted-out.tpl
<div class="list-container">
    {{{list}}}
</div>
_delimiter_6nomlrqbt5x
modules/crm/res/templates/meeting/popup-notification.tpl
{{#if closeButton}}
<a role="button" tabindex="0" class="pull-right close" data-action="close" aria-hidden="true"><span class="fas fa-times"></span></a>
{{/if}}
<h4>{{header}}</h4>


<div class="cell form-group">
    <div class="field">
        <a
            href="#{{notificationData.entityType}}/view/{{notificationData.id}}"
            data-action="close"
        >{{notificationData.name}}</a>
    </div>

</div>

<div class="cell form-group" data-name="{{dateField}}">
    <div class="field" data-name="{{dateField}}">
        {{{date}}}
    </div>
</div>


_delimiter_6nomlrqbt5x
modules/crm/res/templates/meeting/fields/reminders/edit.tpl
<div class="reminders-container"></div>
<button
    data-action="addReminder"
    class="btn btn-default"
    type="button"
><span class="fas fa-plus"></span></button>

_delimiter_6nomlrqbt5x
modules/crm/res/templates/meeting/fields/reminders/detail.tpl
{{#if value}}
    {{{value}}}
{{else}}
    <span class="none-value">{{translate 'None'}}</span>
{{/if}}

_delimiter_6nomlrqbt5x
modules/crm/res/templates/lead/convert.tpl
<div class="header page-header">{{{header}}}</div>

{{#each scopeList}}
<div class="record">
    <label style="user-select: none; cursor: pointer;" class="text-large">
        <input
            type="checkbox"
            class="check-scope form-checkbox"
            data-scope="{{./this}}"
        >
        <span>{{translate this category='scopeNames'}}</span>
    </label>
    <div class="edit-container-{{toDom this}} hide">
    {{{var this ../this}}}
    </div>
</div>
{{/each}}

<div class="button-container margin-top">
    <div class="btn-group">
        <button class="btn btn-primary" data-action="convert">{{translate 'Convert' scope='Lead'}}</button>
        <button class="btn btn-default" data-action="cancel">{{translate 'Cancel'}}</button>
    </div>
</div>

_delimiter_6nomlrqbt5x
modules/crm/res/templates/knowledge-base-article/list.tpl
<div class="page-header">{{{header}}}</div>
<div class="search-container">{{{search}}}</div>

<div class="row">
    {{#unless categoriesDisabled}}
    <div class="categories-container{{#unless categoriesDisabled}} col-md-3 col-sm-4{{else}} col-md-12{{/unless}}">{{{categories}}}</div>
    {{/unless}}
    <div class="list-container{{#unless categoriesDisabled}} col-md-9 col-sm-8{{else}} col-md-12{{/unless}}">{{{list}}}</div>
</div>


_delimiter_6nomlrqbt5x
modules/crm/res/templates/knowledge-base-article/modals/select-records.tpl
<div class="search-container">{{{search}}}</div>

<div class="row">
    {{#unless categoriesDisabled}}
    <div class="categories-container{{#unless categoriesDisabled}} col-md-3 col-sm-4{{else}} col-md-12{{/unless}}">{{{categories}}}</div>
    {{/unless}}
    <div class="list-container{{#unless categoriesDisabled}} col-md-9 col-sm-8{{else}} col-md-12{{/unless}}">{{{list}}}</div>
</div>

{{#if createButton}}
<div class="button-container">
    <button class="btn btn-default" data-action="create">{{translate 'Create'}}</button>
</div>
{{/if}}

_delimiter_6nomlrqbt5x
modules/crm/res/templates/event-confirmation/confirmation.tpl
<div class="container content">
    <div class="block-center-md">
        <div class="panel panel-default">
            <div class="panel-body">
                <h4 class="margin-bottom-2x">{{actionData.translatedEntityType}}: {{actionData.eventName}}</h4>
                {{#if dateStart}}
                <div class="margin-bottom-2x">
                    {{#if dateStartChanged}}
                    <div style="text-decoration: line-through;">{{sentDateStart}}</div>
                    {{/if}}
                    <div>{{dateStart}}</div>
                </div>
                {{/if}}
                <div>
                    <span class="label label-{{style}} label-md">{{actionData.translatedStatus}}</span>
                    &nbsp;<div class="btn-group">
                        {{#if actionDataList}}
                        <a role="button" class="dropdown-toggle text-soft" data-toggle="dropdown">
                            <span class="fas fa-ellipsis-h"></span>
                        </a>
                        <ul class="dropdown-menu">
                            {{#each actionDataList}}
                            <li>
                                <a {{#if link}}href="{{link}}"{{/if}}>{{label}}
                                    {{#if active}}<span class="fas fa-check pull-right"></span>{{/if}}
                                </a>
                            </li>
                            {{/each}}
                        </ul>
                        {{/if}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
modules/crm/res/templates/document/list.tpl
<div class="page-header">{{{header}}}</div>
<div class="search-container">{{{search}}}</div>

<div class="row">
    {{#unless categoriesDisabled}}
    <div class="categories-container{{#unless categoriesDisabled}} col-md-3 col-sm-4{{else}} col-md-12{{/unless}}">{{{categories}}}</div>
    {{/unless}}
    <div class="list-container{{#unless categoriesDisabled}} col-md-9 col-sm-8{{else}} col-md-12{{/unless}}">{{{list}}}</div>
</div>

_delimiter_6nomlrqbt5x
modules/crm/res/templates/document/modals/select-records.tpl
<div class="search-container">{{{search}}}</div>

<div class="row">
    {{#unless categoriesDisabled}}
    <div class="categories-container{{#unless categoriesDisabled}} col-md-3 col-sm-4{{else}} col-md-12{{/unless}}">{{{categories}}}</div>
    {{/unless}}
    <div class="list-container{{#unless categoriesDisabled}} col-md-9 col-sm-8{{else}} col-md-12{{/unless}}">{{{list}}}</div>
</div>

{{#if createButton}}
<div class="button-container">
    <button class="btn btn-default" data-action="create">{{translate 'Create'}}</button>
</div>
{{/if}}

_delimiter_6nomlrqbt5x
modules/crm/res/templates/contact/fields/account-role/detail.tpl
{{#if accountIsInactive}}<del>{{/if}}<span title="{{value}}">{{value}}</span>{{#if accountIsInactive}}</del>{{/if}}

_delimiter_6nomlrqbt5x
modules/crm/res/templates/campaign-log-record/fields/data/detail.tpl
{{{value}}}
_delimiter_6nomlrqbt5x
modules/crm/res/templates/campaign/unsubscribe.tpl
<div class="container content">
    <div class="block-center-md">
        <div class="panel panel-default">
            <div class="panel-body">
                <p>
                    {{#if isSubscribed}}
                        <a
                            class="btn btn-primary{{#if inProcess}} disabled{{/if}}"
                            data-action="unsubscribe"
                        >{{translate 'Unsubscribe' scope='Campaign'}}</a>
                    {{else}}
                        <a
                            class="btn btn-default{{#if inProcess}} disabled{{/if}}"
                            data-action="subscribe"
                        >{{translate 'Subscribe again' scope='Campaign'}}</a>
                    {{/if}}
                </p>
            </div>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
modules/crm/res/templates/campaign/modals/mail-merge-pdf.tpl
<div class="row">
    <div class="cell col-md-6">
        <div class="field" data-name="link">
            <select class="form-control" data-name="link">
                {{#each linkList}}
                <option value="{{./this}}">{{translate this category='links' scope='TargetList'}}</option>
                {{/each}}
            </select>
        </div>
    </div>
</div>

_delimiter_6nomlrqbt5x
modules/crm/res/templates/calendar/timeline.tpl
<link href="{{basePath}}client/modules/crm/css/vis.css" rel="stylesheet">

{{#if header}}
<div class="row button-container">
    <div class="col-sm-4 col-xs-12">
        <div class="btn-group">
            <button
                class="btn btn-text btn-icon"
                title="{{translate 'Refresh'}}"
                data-action="refresh"
            ><span class="fas fa-sync-alt"></span></button>
            <button
                class="btn btn-text"
                data-action="today"
            >{{translate 'Today' scope='Calendar'}}</button>
        </div>{{#if calendarTypeSelectEnabled}}<div class="btn-group calendar-type-button-group">
        <div class="btn-group " role="group">
            <button
                type="button"
                class="btn btn-text dropdown-toggle"
                data-toggle="dropdown"
            ><span class="calendar-type-label">{{calendarTypeLabel}}</span> <span class="caret"></span></button>
            <ul class="dropdown-menu">
                {{#each calendarTypeDataList}}
                    <li>
                        <a role="button" tabindex="0" data-action="toggleCalendarType" data-name="{{type}}">
                            <span
                                class="fas fa-check calendar-type-check-icon pull-right{{#if disabled}} hidden{{/if}}"
                            ></span> {{label}}
                        </a>
                    </li>
                {{/each}}
            </ul>
        </div>
        <button
            class="btn btn-text{{#ifNotEqual calendarType 'shared'}} hidden{{/ifNotEqual}} btn-icon"
            data-action="showSharedCalendarOptions"
            title="{{translate 'Shared Mode Options' scope='Calendar'}}"
        ><span class="fas fa-pencil-alt fa-sm"></span></button>
        </div>
        {{/if}}
    </div>

    <div class="date-title col-sm-4 hidden-xs">
    <h4><span style="cursor: pointer;" data-action="refresh" title="{{translate 'Refresh'}}"></span></h4></div>

    <div class="col-sm-4 col-xs-12">
        <div class="btn-group pull-right mode-buttons">
            {{{modeButtons}}}
        </div>
    </div>
</div>
{{/if}}

<div class="timeline"></div>

_delimiter_6nomlrqbt5x
modules/crm/res/templates/calendar/mode-buttons.tpl
{{#each visibleModeDataList}}
<button class="btn btn-text strong{{#ifEqual mode ../mode}} active{{/ifEqual}}" data-action="mode" data-mode="{{mode}}" title="{{label}}"><span class="hidden-md hidden-sm hidden-xs">{{label}}</span><span class="visible-md visible-sm visible-xs">{{labelShort}}</span></button>
{{/each}}
<div class="btn-group" role="group">
    <button type="button" class="btn btn-text dropdown-toggle" data-toggle="dropdown"><span class="fas fa-ellipsis-h"></span></button>
    <ul class="dropdown-menu pull-right">
        {{#each hiddenModeDataList}}
            <li>
                <a
                    role="button"
                    tabindex="0"
                    class="{{#ifEqual mode ../mode}} active{{/ifEqual}}"
                    data-action="mode"
                    data-mode="{{mode}}"
                >{{label}}</a>
            </li>
        {{/each}}
        {{#if hiddenModeDataList.length}}
            <li class="divider"></li>
        {{/if}}
        {{#each scopeFilterDataList}}
            <li>
                <a
                    role="button"
                    tabindex="0"
                    data-action="toggleScopeFilter"
                    data-name="{{scope}}"
                >
                    <span class="fas fa-check filter-check-icon check-icon pull-right{{#if disabled}} hidden{{/if}}"></span>
                    <div>{{translate scope category='scopeNamesPlural'}}</div>

                </a>
            </li>
        {{/each}}
        {{#if hasMoreItems}}
            <li class="divider"></li>
        {{/if}}
        {{#if isCustomViewAvailable}}
            <li>
                <a
                    role="button"
                    tabindex="0"
                    data-action="createCustomView"
                >{{translate 'Create Shared View' scope='Calendar'}}</a>
            </li>
        {{/if}}
        {{#if hasWorkingTimeCalendarLink}}
            <li>
                <a href="#WorkingTimeCalendar">{{translate 'WorkingTimeCalendar' category='scopeNamesPlural'}}</a>
            </li>
        {{/if}}
    </ul>
</div>

_delimiter_6nomlrqbt5x
modules/crm/res/templates/calendar/calendar.tpl
{{#if header}}
<div class="row button-container">
    <div class="col-sm-4 col-xs-5">
        <div class="btn-group range-switch-group">
            <button class="btn btn-text btn-icon" data-action="prev"><span class="fas fa-chevron-left"></span></button>
            <button class="btn btn-text btn-icon" data-action="next"><span class="fas fa-chevron-right"></span></button>
        </div>
        <div class="btn-group range-switch-group">
        <button class="btn btn-text strong" data-action="today" title="{{todayLabel}}">
            <span class="hidden-sm hidden-xs">{{todayLabel}}</span><span class="visible-sm visible-xs">{{todayLabelShort}}</span>
        </button>
        </div>

        <button
            class="btn btn-text{{#unless isCustomView}} hidden{{/unless}} btn-icon"
            data-action="editCustomView"
            title="{{translate 'Edit'}}"
        ><span class="fas fa-pencil-alt fa-sm"></span></button>
    </div>

    <div class="date-title col-sm-4 col-xs-7">
    <h4><span style="cursor: pointer;" data-action="refresh" title="{{translate 'Refresh'}}"></span></h4></div>

    <div class="col-sm-4 col-xs-12">
        <div class="btn-group pull-right mode-buttons">
            {{{modeButtons}}}
        </div>
    </div>
</div>
{{/if}}

<div class="calendar"></div>

_delimiter_6nomlrqbt5x
modules/crm/res/templates/calendar/calendar-page.tpl
<div class="calendar-container no-window-scroll">
    {{{calendar}}}
</div>

_delimiter_6nomlrqbt5x
modules/crm/res/templates/calendar/modals/edit.tpl
{{#if isNew}}
<div class="scope-switcher radio-container">
{{#each scopeList}}
    <div>
        <label class="radio-label">
            <input
                type="radio"
                name="scope"
                class="form-radio"
                {{#ifEqual this ../scope}} checked{{/ifEqual}}
                value="{{./this}}"
            >
            {{translate this category='scopeNames'}}
        </label>
    </div>
{{/each}}
</div>
{{/if}}

<div class="edit-container record no-side-margin">{{{edit}}}</div>
