<ul class="pagination">
    <li {{#unless previous}}class="disabled"{{/unless}}>
        <a class="pagination-btn" role="button" tabindex="0" data-page="first"><i class="fas fa-fast-backward"></i></a>
    </li>
    <li {{#unless previous}}class="disabled"{{/unless}}>
        <a class="pagination-btn" role="button" tabindex="0" data-page="previous"><i class="fas fa-backward"></i></a>
    </li>
    <li>
        <a
            class="pagination-btn-middle"
            role="button"
            tabindex="0"
            data-page="current"
        >{{from}} - {{to}}{{#unless noTotal}} &middot; {{total}}{{/unless}}</a>
    </li>
    <li {{#unless next}}class="disabled"{{/unless}}>
        <a class="pagination-btn" role="button" tabindex="0" data-page="next"><i class="fas fa-forward"></i></a>
    </li>
    <li {{#unless next}}class="disabled"{{/unless}}>
        <a class="pagination-btn" role="button" tabindex="0" data-page="last"><i class="fas fa-fast-forward"></i></a>
    </li>
</ul>
