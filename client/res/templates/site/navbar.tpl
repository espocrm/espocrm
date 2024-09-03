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
        <a class="minimizer hidden" role="button" tabindex="0">
            <span class="fas fa-chevron-right right"></span>
            <span class="fas fa-chevron-left left"></span>
        </a>
    </div>
</div>
