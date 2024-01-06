<div class="header page-header"><div class="page-header-row">
    <div class="page-header-column-1">
        <h3 class="header-title"><div class="header-breadcrumbs"><div class="breadcrumb-item"><span><span class="fas fa-calculator"></span><span style="user-select: none;">&nbsp;</span>Зарплати</span></div><div class="breadcrumb-separator"><span class="chevron-right"></span></div><div class="breadcrumb-item"><span>розрахунок</span></div></div></h3>
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
            {{/ifEqual}}
        </div>
        <div>
            <label class="control-label small">Педагоги</label>
        </div>
        <div class="margin-bottom">
            <button id="chooseTrainers" class="btn btn-default">Обрати</button>
            {{#ifEqual userListTotal 0}}
                <label class="control-label small">Не обрано</label>
            {{else}}
                <button id="calculate" title="Перерахувати" class="btn btn-primary btn-icon-wide"><span class="fas fa-calculator"></span></button>
            {{/ifEqual}}
        </div>
        <div>
            <label class="control-label small">Загалом</label>
        </div>
        <div class="margin-bottom">
            <span class="btn btn-warning">{{totalSalary}}</span>
            <label class="control-label small">UAH</label>
        </div>
    </div>
</div>
{{#if trainers}}
    <table class="table" style="border-radius: 5px">
        <tbody>
            {{#each trainers}}
                <tr data-id="652e91302a5b8bc2c" class="list-row">  
                    <td class="cell col-sm-3" data-name="name">
                        <a class="link" title={{this.name}}>{{this.name}}</a></br>
                        {{#if this.trainerCategoryName}}
                            <span class="label label-default">{{this.trainerCategoryName}}</span>
                        {{else}}
                            <span class="label label-danger">Немає категорії</span>
                        {{/if}}
                    </td>
                    <td class="cell" data-name="amount">
                        <span class="label label-md label-warning" title="">
                            ₴{{this.totalAmountResult}}
                        </span>
                        {{#if this.finesTotalAmount}}
                            <label class="control-label small">
                                &nbsp;
                                ({{this.totalAmount}} - {{this.finesTotalAmount}})
                            </label>
                        {{/if}}
                        <table class="table" style="border-radius: 5px">
                            {{{this.trainingsTemplate}}}
                        </table> 
                    </td>
                </tr>
            {{/each}}
        </tbody>
    </table>
{{else}}
    <span class="btn btn-text btn-warning btn-full-wide">Немає даних</span>
{{/if}}