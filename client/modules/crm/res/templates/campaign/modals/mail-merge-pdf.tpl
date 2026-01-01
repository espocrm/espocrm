<div class="row">
    <div class="cell col-md-6">
        <div class="field" data-name="link">
            <select class="form-control" data-name="link">
                {{#each linkList}}
                <option value="{{./this}}">{{translate this category='links' scope='TargetList'}}</option>
                {{/each}}
            </select>
        </div>
    </div>
</div>
