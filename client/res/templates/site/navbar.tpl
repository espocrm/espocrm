<div class="navbar navbar-inverse" role="navigation">
    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-action="toggleCollapsable">
            <span class="fas fa-bars"></span>
        </button>
        <div class="navbar-logo-container"><a class="navbar-brand nav-link" href="#"><img src="{{logoSrc}}" class="logo"></span></a></div>
        <a href="javascript:" class="side-menu-button"><span class="fas fa-bars"></span></a>
    </div>

    <div class="navbar-collapse navbar-body">
        <ul class="nav navbar-nav tabs">
            {{#each tabDefsList}}
            {{#unless isInMore}}
            <li data-name="{{name}}" class="not-in-more tab{{#if isGroup}} tab-group dropdown{{/if}}">
                <a
                    href="{{link}}"
                    class="{{aClassName}}"
                    {{#if color}}
                        style="border-color: {{color}}"
                    {{/if}}
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
                    <span class="fas fa-caret-right group-caret"><span>
                    {{/if}}
                </a>
                {{#if isGroup}}
                <ul class="dropdown-menu" role="menu" aria-labelledby="nav-tab-group-{{name}}">
                    {{#each itemList}}
                    <li data-name="{{name}}" class="in-group tab">
                        <a
                            href="{{link}}"
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
                    {{/each}}
                </ul>
                {{/if}}
            </li>
            {{/unless}}
            {{/each}}
            <li class="dropdown more">
                <a
                    id="nav-more-tabs-dropdown"
                    class="dropdown-toggle"
                    data-toggle="dropdown"
                    href="#"
                ><span class="fas fa-ellipsis-h more-icon"></span></a>
                <ul class="dropdown-menu more-dropdown-menu" role="menu" aria-labelledby="nav-more-tabs-dropdown">
                {{#each tabDefsList}}
                {{#if isInMore}}
                    <li
                        data-name="{{name}}"
                        class="in-more tab{{#if className}} {{className}}{{/if}}{{#if isGroup}} dropdown tab-group{{/if}}"
                    >
                        <a
                            href="{{link}}"
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
                            <span class="fas fa-caret-right group-caret"><span>
                            {{/if}}
                        </a>
                        {{#if isGroup}}
                        <ul class="dropdown-menu" role="menu" aria-labelledby="nav-tab-group-{{name}}">
                            {{#each itemList}}
                            <li data-name="{{name}}" class="in-group tab">
                                <a
                                    href="{{link}}"
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
                            {{/each}}
                        </ul>
                        {{/if}}
                    </li>
                {{/if}}
                {{/each}}
                </ul>
            </li>
        </ul>
        <div class="navbar-right-container">
        <ul class="nav navbar-nav navbar-right">
            <li class="nav navbar-nav navbar-form global-search-container">
                {{{globalSearch}}}
            </li>
            <li class="dropdown notifications-badge-container">
                {{{notificationsBadge}}}
            </li>
            {{#if enableQuickCreate}}
            <li class="dropdown hidden-xs quick-create-container">
                <a id="nav-quick-create-dropdown" class="dropdown-toggle" data-toggle="dropdown" href="#" title="{{translate 'Create'}}"><i class="fas fa-plus"></i></a>
                <ul class="dropdown-menu" role="menu" aria-labelledby="nav-quick-create-dropdown">
                    <li class="dropdown-header">{{translate 'Create'}}</li>
                    {{#each quickCreateList}}
                    <li><a href="#{{./this}}/create" data-name="{{./this}}" data-action="quick-create">{{translate this category='scopeNames'}}</a></li>
                    {{/each}}
                </ul>
            </li>
            {{/if}}
            <li class="dropdown menu-container">
                <a id="nav-menu-dropdown" class="dropdown-toggle" data-toggle="dropdown" href="#" title="{{translate 'Menu'}}"><span class="fas fa-ellipsis-v"></span></a>
                <ul class="dropdown-menu" role="menu" aria-labelledby="nav-menu-dropdown">
                    {{#each menuDataList}}
                        {{#unless divider}}
                            <li><a href="{{#if link}}{{link}}{{else}}javascript:{{/if}}" class="nav-link{{#if action}} action{{/if}}"{{#if action}} data-action="{{action}}"{{/if}}>{{#if html}}{{{html}}}{{else}}{{label}}{{/if}}</a></li>
                        {{else}}
                            <li class="divider"></li>
                        {{/unless}}
                    {{/each}}
                </ul>
            </li>
        </ul>
        </div>
        <a class="minimizer" href="javascript:">
            <span class="fas fa-chevron-right right"></span>
            <span class="fas fa-chevron-left left"></span>
        </a>
    </div>
</div>
