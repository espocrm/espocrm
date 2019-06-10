<ul class="list-group no-side-margin">
{{#each typeList}}
    <li class="list-group-item">
        <a href="javascript:" data-action="addField" data-type="{{./this}}">
        {{translate this category='fieldTypes' scope='Admin'}}
        </a>
    </li>
{{/each}}
</ul>
