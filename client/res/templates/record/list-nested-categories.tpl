
{{#unless isLoading}}
<div class="list-nested-categories">
    <div class="row">
        {{#each list}}
            <div class="col-md-3 col-sm-6 col-xs-6">
                <div class="category-item" data-id="{{id}}">
                    <a href="#{{../scope}}/view/{{id}}" class="action folder-icon" data-action="openCategory" data-id="{{id}}" data-name={{name}}><span class="fas fa-folder text-muted fa-sm"></span></a>
                    <a href="#{{../scope}}/view/{{id}}" class="action" data-action="openCategory" data-id="{{id}}" data-name={{name}}>{{name}}</a>
                </div>
            </div>
        {{/each}}
        {{#if showMoreIsActive}}
            <div class="col-md-3 col-sm-6 col-xs-6">
                <div class="category-item show-more">
                    <span class="category-item-name">
                        <a href="javascript:" class="action" data-action="showMore" title="{{translate 'Show more'}}">...</a>
                    </span>
                </div>
            </div>
        {{/if}}
    </div>
</div>
{{else}}
<div class="list-nested-categories">
    <div class="row">
        <div class="col-md-3 col-sm-6 col-xs-6">
            <div class="col-md-3 col-sm-6 col-xs-6">
                <div class="category-item">
                ...
                </div>
            </div>
        </div>
    </div>
</div>
{{/unless}}