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
