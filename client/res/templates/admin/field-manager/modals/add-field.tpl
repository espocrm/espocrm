<ul class="list-group no-side-margin">
{{#each typeList}}
    <li class="list-group-item">
        <a href="javascript:" data-action="addField" data-type="{{./this}}" class="text-bold">
        {{translate this category='fieldTypes' scope='Admin'}}
        </a>
        <a href="javascript:" class="text-muted pull-right info" data-name="{{./this}}">
            <span class="fas fa-info-circle"></span>
        </a>
    </li>
{{/each}}
</ul>
