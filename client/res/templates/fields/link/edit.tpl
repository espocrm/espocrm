<div class="input-group">
    <input
        class="main-element form-control"
        type="text"
        data-name="{{nameName}}"
        value="{{nameValue}}"
        autocomplete="espo-{{name}}"
        placeholder="{{translate 'Select'}}"
        spellcheck="false"
    >
    <span class="input-group-btn">
        <button data-action="selectLink" class="btn btn-default btn-icon" type="button" tabindex="-1" title="{{translate 'Select'}}"><i class="fas fa-angle-up"></i></button>
        <button data-action="clearLink" class="btn btn-default btn-icon" type="button" tabindex="-1"><i class="fas fa-times"></i></button>
    </span>
</div>
<input type="hidden" data-name="{{idName}}" value="{{idValue}}">
