<ul class="pagination clearfix">
    <li {{#unless previous}}class="disabled"{{/unless}}>
        <a
            class="pagination-btn btn-btn-default"
            role="button"
            tabindex="0"
            data-page="previous"
        ><span class="fas fa-chevron-left"></span></a>
    </li>
    <li class="{{#unless previous}}disabled{{/unless}} hidden">
        <a
            class="pagination-btn btn-btn-default"
            role="button" tabindex="0"
            data-page="first"
        >1</a>
    </li>
    <li>
        <a
            class="pagination-btn-middle btn-btn-default"
            role="button"
            tabindex="0"
            data-page="current"
            {{#unless noTotal}}title="{{translate 'Total'}}: {{total}}"{{/unless}}
        >{{from}} - {{to}}</a>
    </li>
    <li class="{{#unless last}}disabled{{/unless}} hidden">
        <a
            class="pagination-btn btn-btn-default"
            role="button"
            tabindex="0"
            data-page="last"
        ><span class="fas fa-step-forward"></span></a>
    </li>
    <li {{#unless next}}class="disabled"{{/unless}}>
        <a
            class="pagination-btn btn-btn-default"
            role="button"
            tabindex="0"
            data-page="next"
        ><span class="fas fa-chevron-right"></span></a>
    </li>
</ul>
