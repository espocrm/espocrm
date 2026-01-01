
<div class="input-group-container-2">
<div class="input-group">
    <input class="main-element form-control numeric-text" type="text" data-name="{{name}}" value="{{date}}" autocomplete="espo-{{name}}">
    <span class="input-group-btn">
        <button type="button" class="btn btn-default btn-icon date-picker-btn" tabindex="-1"><i class="far fa-calendar"></i></button>
    </span>
</div>
<div class="input-group">
    <input
        class="form-control time-part numeric-text"
        type="text"
        data-name="{{name}}-time"
        value="{{time}}"
        autocomplete="espo-{{name}}"
        spellcheck="false"
    >
    <span class="input-group-btn time-part-btn">
        <button type="button" class="btn btn-default btn-icon time-picker-btn" tabindex="-1"><i class="far fa-clock"></i></button>
    </span>
</div>
</div>
