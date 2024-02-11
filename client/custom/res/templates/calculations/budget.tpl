<div class="header page-header"><div class="page-header-row">
    <div class="page-header-column-1">
        <h3 class="header-title"><div class="header-breadcrumbs"><div class="breadcrumb-item"><span><span class="fas fa-poll"></span><span style="user-select: none;">&nbsp;</span>Бюджет</span></div><div class="breadcrumb-separator"><span class="chevron-right"></span></div><div class="breadcrumb-item"><span>звіт</span></div></div></h3>
    </div>
</div>
<div>
    <div class="filter filter-date" data-name="date">
        <div>
            <label class="control-label small" data-name="date">Фільтр записів</label>
        </div>
        <div>
            {{#if isSuperadmin}}
                {{#each teams}} 
                    <button value={{this.id}} class="btn-team btn btn-default">{{this.name}}</button>
                {{/each}}
            {{else}}
                {{#each teams}} 
                    <button disabled="disabled" value={{this.id}} class="btn-team btn btn-default">{{this.name}}</button>
                {{/each}}
            {{/if}}
        </div>
        <div>
            <label class="control-label small" data-name="date">Фільтр дати</label>
        </div>
        <div id="filterButtons">
            <button data-action="filterToday" class="btn-date btn btn-default margin-bottom" value="today">Сьогодні</button>
            <button data-action="filterWeek" class="btn-date btn btn-default" value="week">Тиждень</button>
            <button data-action="filterMonth" class="btn-date btn btn-default" value="month">Місяць</button>
            <button data-action="filterBetweenDates" class="btn-date btn btn-default" value="between">Проміжок</button>
        </div>
        <div>
            <label class="control-label small" data-name="date">Дата</label>
        </div>
        <div>
            {{#ifEqual filterValue 'today'}}
                <input id="dateToday" class="btn btn-default" type="date" value={{dateFrom}} disabled="disabled"></input>
            {{/ifEqual}}
            {{#ifEqual filterValue 'week'}}
                <input id="dateBetween1" class="btn btn-default" type="date" value={{dateFrom}} disabled="disabled"></input>
                <input id="dateBetween2" class="btn btn-default" type="date" value={{dateTo}} disabled="disabled"></input>
            {{/ifEqual}}
            {{#ifEqual filterValue 'month'}}
                <input id="dateBetween1" class="btn btn-default" type="date" value={{dateFrom}} disabled="disabled"></input>
                <input id="dateBetween2" class="btn btn-default" type="date" value={{dateTo}} disabled="disabled"></input>
            {{/ifEqual}}
            {{#ifEqual filterValue 'between'}}
                <input id="dateBetween1" class="btn btn-default margin-bottom" type="date" value={{dateFrom}}></input>
                <input id="dateBetween2" class="btn btn-default" type="date" value={{dateTo}}></input>
                <button id="findBetweenDates" class="btn btn-primary btn-icon-wide">
                    <span class="fas fa-search"></span>
                </button>
            {{/ifEqual}}
        </div>
        <div>
            <label class="control-label small">Звіт</label>
        </div>
    </div>
</div>

<table class="table table-hover" style="border-radius: 5px">
    <thead>
        <tr>
            <th data-name="name">
                
            </th>
            <th data-name="cost">
                <a role="button">Дохід</a>
            </th>
            <th data-name="date">
                <a role="button">Витрати</a>
            </th>
            <th data-name="description">
                <a role="button">Прибуток</a>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr class="list-row text-bold" style="background-color: ;">  
            <td class="cell">
                Загалом
            </td> 
            <td class="cell" style="">
                <span class="label label-md label-info">
                    <span style="padding: 3px">
                        {{ profitTotalSum }}
                    </span>
                </span>
            </td>
            <td class="cell">
                <span class="label label-md label-info">
                    <span style="padding: 3px">
                        {{ expensesTotalSum }}
                    </span>
                </span>
            </td>
            <td class="cell">
                <span class="label label-md label-info">
                    {{ incomeTotalSum }}
                </span>
            </td>
        </tr>
        {{#each incomeList}}
            <tr class="list-row">  
                <td class="cell nowrap">
                    {{#if this.isExpanded}}
                        <span data-date={{this.date}} data-action="hideDetails" class="expander cp text-soft far fa-caret-square-up"></span>
                    {{else}}
                        <span data-date={{this.date}} data-action="showDetails" class="expander cp text-soft far fa-caret-square-down"></span>
                    {{/if}}
                    {{this.date}}
                </td> 
                <td class="cell" style="">
                    <span class="label label-md label-primary">
                        <span style="padding: 3px">{{ this.profit }}</span>
                    </span>
                    {{#if this.isExpanded}}
                        {{{ this.profitDetailsTable }}}
                    {{/if}}
                </td>
                <td class="cell" style="">
                    <span class="label label-md label-primary">
                        <span style="padding: 3px">{{ this.expenses }}</span>
                    </span>
                    {{#if this.isExpanded}}
                        {{{ this.expensesDetailsTable }}}
                    {{/if}}
                </td>
                <td class="cell text-bold">
                    {{#if this.isIncome}}
                        <span class="label label-md label-success">
                            {{ this.income }}
                        </span> 
                    {{else}}
                        <span class="label label-md label-danger">
                            {{ this.income }}
                        </span>
                    {{/if}}
                </td>
            </tr>
        {{/each}}
    </tbody>
</table>
