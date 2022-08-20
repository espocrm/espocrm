{{#unless isLoading}}
<div class="list-nested-categories">

    <div class="clearfix">
        <div class="btn-group pull-right">
            <a role="button" tabindex="0" class="dropdown-toggle btn btn-text" data-toggle="dropdown">
                <span class="fas fa-ellipsis-h"></span>
            </a>

            <ul class="dropdown-menu">
                {{#if showEditLink}}
                <li>
                    <a
                        href="#{{scope}}"
                        class="action manage-categories-link"
                        data-action="manageCategories"
                    >{{translate 'Manage Categories' scope=scope}}</a>
                </li>
                <li class="divider"></li>
                {{/if}}

                {{#if hasExpandedToggler}}
                <li class="{{#if isExpanded}}hidden{{/if}}">
                    <a
                        role="button"
                        tabindex="0"
                        class="category-expanded-toggle-link action"
                        data-action="expand"
                    >{{translate 'Expand'}}</a>
                </li>
                {{/if}}

                <li>
                    <a
                        role="button"
                        tabindex="0"
                        class="navigation-toggle-link action"
                        data-action="toggleNavigationPanel"
                    >
                        {{#if hasNavigationPanel}}
                            {{translate 'Hide Navigation Panel'}}
                        {{else}}
                            {{translate 'Show Navigation Panel'}}
                        {{/if}}
                    </a>
                </li>

            </ul>
        </div>
        {{#if currentId}}
        <div class="category-item">
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
</div>
{{/unless}}
