<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='searchRanges'}}
</select>

<div class="link-group-container hidden">

    <div class="link-container list-group"></div>

    <div class="input-group add-team">
        <input
            class="main-element form-control input-sm"
            type="text"
            value=""
            autocomplete="espo-{{name}}"
            placeholder="{{translate 'Select'}}"
            spellcheck="false"
        >
        <span class="input-group-btn">
            <button
                data-action="selectLink"
                class="btn btn-default btn-icon btn-sm"
                type="button"
                title="{{translate 'Select'}}"
            ><span class="fas fa-angle-up"></span></button>
        </span>
    </div>

    <input type="hidden" data-name="{{name}}Ids" value="{{searchParams.value}}" class="ids">
</div>
