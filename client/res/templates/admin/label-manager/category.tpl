{{#each categoryDataList}}
<div class="row" data-name="{{name}}">
    <div class="cell col-md-5 form-group">
        <div class="field detail-field-container">{{label}}</div>
    </div>
    <div class="cell col-md-7 form-group" data-name="{{name}}">
        <div class="field">
            <input type="input" class="form-control label-value" value="{{value}}" data-name="{{name}}">
        </div>
    </div>
</div>
{{/each}}
