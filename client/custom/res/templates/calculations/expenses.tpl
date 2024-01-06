<div class="header page-header"><div class="page-header-row">
    <div class="page-header-column-1">
        <h3 class="header-title"><div class="header-breadcrumbs"><div class="breadcrumb-item"><span><span class="fas fa-calculator"></span><span style="user-select: none;">&nbsp;</span><a href="#Expenses" class="action" data-action="navigateToRoot">Витрати</a></span></div><div class="breadcrumb-separator"><span class="chevron-right"></span></div><div class="breadcrumb-item"><span>розрахунок</span></div></div></h3>
    </div>
</div>
<div>
    <div class="filter filter-date" data-name="date">
        <div>
            <label class="control-label small" data-name="date">Фільтр</label>
        </div>
        <div id="filterButtons">
            <button id="filterToday" class="btn btn-default" value="today">Сьогодні</button>
            <button id="filterDate" class="btn btn-default" value="date">Дата</button>
            <button id="filterBetween" class="btn btn-default" value="between">Проміжок</button>
        </div>
        <div>
            <label class="control-label small" data-name="date">Дата</label>
        </div>
        <div>
            {{#ifEqual filterValue 'today'}}
                <input id="dateToday" class="btn btn-default" type="date" value={{dateValue1}} disabled="disabled"></input>
            {{/ifEqual}}
            {{#ifEqual filterValue 'date'}}
                <input id="dateDate" class="btn btn-default" type="date" value={{dateValue1}}></input>
            {{/ifEqual}}    
            {{#ifEqual filterValue 'between'}}
                <input id="dateBetween1" class="btn btn-default" type="date" value={{dateValue1}}></input>
                <input id="dateBetween2" class="btn btn-default" type="date" value={{dateValue2}}></input>
                <button id="findBetween" class="btn btn-default btn-icon-wide"><span class="fas fa-search"></span></button>
            {{/ifEqual}}
        </div>
        <div>
            <label class="control-label small">Загалом</label>
        </div>
        <div class="margin-bottom">
            <span class="btn btn-warning">{{sum}}</span>
            <label class="control-label small">UAH</label>
        </div>
    </div>
</div>
{{#if expensesTotal}}
    <table class="table" style="border-radius: 5px">
        <thead>
            <tr>
                <th data-name="name">
                    <a role="button">Ім'я</a>
                </th>
                <th data-name="cost">
                    <a role="button">Сума</a>
                </th>
                <th data-name="date">
                    <a role="button">Дата</a>
                </th>
                <th data-name="description">
                    <a role="button">Опис</a>
                </th>
            </tr>
        </thead>
        <tbody>
            {{#each expenses}}
                <tr data-id="652e91302a5b8bc2c" class="list-row">  
                    <td class="cell" data-name="name">
                        <a href="#Expenses/view/{{this.id}}" class="link" title={{this.name}}>{{this.name}}</a>
                    </td> 
                    <td class="cell" data-name="cost">
                        <span title="₴{{this.cost}}">₴{{this.cost}}</span>
                    </td>
                    <td class="cell" data-name="date">
                        <span title="{{this.date}}">{{this.date}}</span>
                    </td>
                    <td class="cell" data-name="description">
                        <span title="{{this.description}}">{{this.description}}</span>
                    </td>
                </tr>
            {{/each}}
        </tbody>
    </table>
{{else}}
    <span class="btn btn-text btn-warning btn-full-wide">Витрат не знайдено</span>
{{/if}}
