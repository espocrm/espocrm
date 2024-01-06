<div class="schedule-container">
    <div class="schedule-main">
        <div class="schedule-main-elem">
            <label>Шаблон: </label>
            <select id="weekTemplate" class="border-bottom" name="weekTemplate">
                {{#each weekTemplates}}
                    <option value={{this.id}} >{{this.name}}</option>
                {{/each}}
            </select>
        </div>
        <div class="schedule-main-elem">
            <label>Тиждень: </label>
            <input id="dateStart" value={{mondayOfWeek}} class="border-bottom" type="date" name="dateStart" style="width: 58%" min="2023-01-01">
        </div>
        {{#if isGenerated}}
            <div class="schedule-main-elem">
                <a href="#Calendar/show/mode=agendaWeek&date={{mondayOfWeek}}">Переглянути</a>
            </div>
        {{else}}
            <div class="schedule-main-elem">
                <button id="createTrainings" class="btn btn-primary">Створити заняття</button>
            </div>
        {{/if}}
    </div>
</div>