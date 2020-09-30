<ul class="list-group no-side-margin">
    <li data-id="inbox" class="list-group-item">
        <a href="javascript:" data-action="selectFolder" data-id="inbox" class="side-link">{{translate 'inbox' category='presetFilters' scope='Email'}}</a>
    </li>
    {{#each collection.models}}
    <li data-id="{{get this 'id'}}" class="list-group-item">
        <a href="javascript:" data-action="selectFolder" data-id="{{get this 'id'}}" class="side-link">{{get this 'name'}}</a>
    </li>
    {{/each}}
</ul>