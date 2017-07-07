{{#each categoryDataList}}
<div class="row">
    <div class="cell col-md-5 form-group">
        <div class="field">
            {{label}}
        </div>
    </div>
    <div class="cell col-md-7 form-group" data-name="{{name}}">
        <div class="field">
            <input type="input" class="form-control label-value" value="{{value}}" data-name="{{name}}">
        </div>
    </div>
</div>
{{/each}}