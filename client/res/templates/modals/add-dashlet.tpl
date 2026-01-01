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
