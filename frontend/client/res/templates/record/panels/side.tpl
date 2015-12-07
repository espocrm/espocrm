<div class="row">
    {{#each fieldList}}
    <div class="cell form-group col-sm-6 col-md-12" data-name="{{./this}}">
        <label class="control-label" data-name="{{./this}}">
            {{translate this scope=../model.name category='fields'}}
        </label>
        <div class="field" data-name="{{./this}}">
        {{{var this ../this}}}
        </div>
    </div>
    {{/each}}
</div>
