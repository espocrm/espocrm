define('admin-panel:views/company/list', ['views/record/list'], function (Dep) {

    return Dep.extend({
        __name__: 'AdminPanel.Company.List',

        template: 'admin-panel:company/list',
    });
});
