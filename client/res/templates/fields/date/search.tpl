<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='dateSearchRanges'}}
</select>
<div class="input-group primary">
    <input
        class="main-element form-control input-sm numeric-text"
        type="text"
        data-name="{{name}}"
        value="{{dateValue}}"
        autocomplete="espo-{{name}}"
    >
    <span class="input-group-btn">
        <button
            type="button"
            class="btn btn-default btn-icon btn-sm date-picker-btn"
            tabindex="-1"
        ><i class="far fa-calendar"></i></button>
    </span>
</div>
<div class="input-group input-daterange{{#ifNotEqual searchType 'between'}} hidden{{/ifNotEqual}} additional">
    <input
        class="main-element form-control input-sm filter-from numeric-text"
        type="text"
        value="{{dateValue}}"
        autocomplete="espo-{{name}}"
    >
    <div class="input-group-addon input-sm"> – </div>
    <input
        class="main-element form-control input-sm filter-to numeric-text"
        type="text"
        value="{{dateValueTo}}"
        autocomplete="espo-{{name}}"
    >
</div>
<div class="hidden additional-number">
    <input
        class="main-element form-control input-sm number numeric-text"
        type="number"
        value="{{number}}"
        placeholder ="{{translate 'Number'}}"
        autocomplete="espo-{{name}}"
    >
</div>
