<div class="navbar navbar-inverse" role="navigation">
    <div class="navbar-header">
        <a class="navbar-brand nav-link" href="#"><img src="{{logoSrc}}" class="logo"><span class="home-icon fas fa-th-large" title="{{translate 'Home'}}"></span></a>
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-body">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
    </div>

    <div class="collapse navbar-collapse navbar-body">
        <ul class="nav navbar-nav tabs">
            {{#each tabDefsList}}
            {{#unless isInMore}}
            <li data-name="{{name}}" class="not-in-more tab">
                <a href="{{link}}" class="nav-link"{{#if color}} style="border-color: {{color}}"{{/if}}>
                    <span class="full-label">{{label}}</span>
                    <span class="short-label" title="{{label}}"{{#if color}} style="color: {{color}}"{{/if}}>
                        {{#if iconClass}}
                        <span class="{{iconClass}}"></span>
                        {{else}}
                        {{#if colorIconClass}}
                        <span class="{{colorIconClass}}" style="color: {{color}}"></span>
                        {{/if}}
                        <span class="short-label-text">{{shortLabel}}</span>
                        {{/if}}
                    </span>
                </a>
            </li>
            {{/unless}}
            {{/each}}
            <li class="dropdown more">
                <a id="nav-more-tabs-dropdown" class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="fas fa-ellipsis-h more-icon"></span></a>
                <ul class="dropdown-menu" role="menu" aria-labelledby="nav-more-tabs-dropdown">
                {{#each tabDefsList}}
                {{#if isInMore}}
                    <li data-name="{{name}}" class="in-more tab">
                        <a href="{{link}}" class="nav-link"{{#if color}} style="border-color: {{color}}"{{/if}}>
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
            </li>
        </ul>
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
                <a id="nav-menu-dropdown" class="dropdown-toggle" data-toggle="dropdown" href="#" title="{{translate 'Menu'}}"><span class="fas fa-bars"></span></a>
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
        <a class="minimizer" href="javascript:">
            <span class="fas fa-chevron-right right"></span>
            <span class="fas fa-chevron-left left"></span>
        </a>
    </div>
</div>
