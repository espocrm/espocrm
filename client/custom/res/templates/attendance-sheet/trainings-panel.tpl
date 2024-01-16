<div class="panel panel-default panel-overflow">
    <div class="panel-heading panel-header">
        <h4 class="panel-title" style='font-size: 1.2em'>
            <span class="fas fa-users" style="padding: 4px 5px 0 0;"></span>
            Заняття: {{activitiesTotal}}
        </h4>
        <div style='display: flex'>
            <span>
                <select class="cp" name="hall" id="hall" value={{activityHall}} style="border-bottom: 1px solid #E4E7F2;">
                    <option value='all'>Всі зали</option>
                    {{#each halls}}
                        <option value={{this.id}}>{{this.name}}</option>
                    {{/each}}
                </select>
            </span>
            <span title="Попередній день" class="cp text-muted fas fa-angle-left date-nav highlight" data-action="prevDay"></span>
            <span>
                <input class="cp" type="date" id="date" name="date" value={{activityDate}} 
                    min="2023-01-01" max="2030-12-31" style="border: none; border-bottom: 1px solid #E4E7F2; background-color: transparent" />
            </span>
            <span title="Наступний день" class="cp text-muted fas fa-angle-right date-nav highlight" data-action="nextDay"></span>
        </div>
    </div>
    <div class="panel-body">
        {{#if activitiesTotal}}
            <table class="table table-hover bb">
                <tr class="text-soft">
                    <td>Група</td>
                    <td>Педагог</td>
                    <td>Початок</td>
                    <td></td>
                </tr>
                {{#each activities}}
                    <tr class="activity cp" data-training-id={{this.id}} data-group-id={{this.groupId}} >
                        <td class="nowrap">{{this.name}}</td>
                        <td>{{this.assignedUserName}}</td>
                        <td>{{this.timeDuration}}</td>
                        <td class="text-muted">
                            <span title="Редагувати" class="training-edit fas fa-pen-square" 
                                data-training-id={{this.id}} data-action="editTraining">
                            </span>
                        </td>
                    </tr>
                {{/each}}
            </table>
         {{else}}
            <div class="text-soft center-align">Немає данних</div>
        {{/if}}
        <div class="abon-panel-buttons">
            <button class="btn btn-sm btn-default btn-add" data-action="addTraining">
                <span class="fas fa-plus"></span>
                Заняття
            </button>
        </div>
    </div>
</div>
