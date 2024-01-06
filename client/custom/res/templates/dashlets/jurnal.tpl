<div class="row">
     <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title" style='font-size: 1.2em'>
                    <span class="fas fa-users" style="padding: 4px 5px 0 0;"></span>
                    Заняття: {{activitiesTotal}}
                </h4>
                <div style='display: flex'>
                    <span>
                        <label for="hall">Зал:</label>
                        <select name="hall" id="hall" value={{activityHall}} style='border: none; border-bottom: 1px solid lightgrey; margin-right: 10px; background-color: transparent;'>
                            <option value='all'>Всі зали</option>
                            {{#each halls}}
                                <option value={{this.id}}>{{this.name}}</option>
                            {{/each}}
                        </select>
                    </span>
                    <span>
                        <label for="date">Дата:</label>
                        <input type="date" id="date" name="date" value={{activityDate}} 
                            min="2023-01-01" max="2030-12-31" style="border: none; border-bottom: 1px solid lightgrey; background-color: transparent" />
                    </span>
                </div>
            </div>
            <div class="panel-body">
                <table class="table table-hover">
                    <tr>
                        <td>Група</td>
                        <td>Педагог</td>
                        <td>Час</td>
                    </tr>
                    {{#each activities}}
                        <tr class="activity" data-training-id={{this.id}} data-group-id={{this.groupId}} style="cursor: pointer;">
                            <td>{{this.name}}</td>
                            <td>{{this.assignedUserName}}</td>
                            <td>{{this.timeDuration}}</td>
                        </tr>
                    {{/each}}
                </table>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title" style='font-size: 1.2em'>
                    <span class="fas fa-id-card" style="padding: 4px 5px 0 0;"></span>
                    Абонементи
                </h4>
                <div>
                    <span>
                        <label>Всього: {{abonementsTotal}}</label>
                        <label style='padding-left: 5px'>Відмічено: {{marksTotal}}</label>
                    </span>
                </div>
            </div>
            <div class="panel-body">
                <table class="table table-hover">
                    <tr>
                        <th class="col-sm-1">Номер</th>
                        <th>Ім'я</th>
                        <th>Залишилось занять</th>
                        <th style="text-align: center">Відмітка</th>
                    </tr>
                    {{#each abonements}}
                        <tr>
                            <td>
                                {{#if this.note}}
                                    <span data-id={{this.id}} style="cursor: pointer;" class="fas fa-exclamation-circle"></span>
                                {{/if}}
                                {{this.number}}
                            </td>
                            <td>
                                <span style="cursor: pointer;" data-id={{this.id}} class="abon-name">{{this.name}}</span>
                            </td>
                            <td>{{this.classesLeft}}</td>
                            <td style="cursor: pointer;" data-abonement-id={{this.id}} data-mark-id={{this.mark.id}}>
                                {{#if this.mark.id}}
                                    <span class="fas fa-check" data-mark-id={{this.mark.id}}></span>
                                {{/if}}
                            </td>
                        </tr>
                    {{/each}}
                </table>
            </div>
        </div>
    </div>
</div>