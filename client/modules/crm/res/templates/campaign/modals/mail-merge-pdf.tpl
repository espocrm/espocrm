
<div class="cell">
    <div class="field" data-name="link">
        <select class="form-control">
        {{#each linkList}}
            <option value="{{./this}}">{{translate this category='links' scope='TargetList'}}</option>
        {{/each}}
        </select>
    </div>
</div>
