<div class="btn-group pagination clearfix">
    <div class="btn-group">
        <a
            class="pagination-btn-middle btn btn-text dropdown-toggle"
            role="button"
            tabindex="0"
            data-toggle="dropdown"
            {{#unless noTotal}}title="{{translate 'Total'}}: {{total}}"{{/unless}}
        >{{from}} - {{to}}{{#unless noTotal}} / {{total}}{{/unless}}</a>
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
                >{{translate 'Last Page'}}</a>
            </li>
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
