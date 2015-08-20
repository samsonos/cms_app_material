/* Form main JS */

s('.material-structure-selectify').pageInit(function(select) {
    select.selectify();

    initLinks(select.prev());

    s('._sjsselect ._sjsselect_delete', select.prev()).each(function(link) {
        link.click(function(link) {
            if (!link.hasClass('selectify-loaded')) {
                s.ajax(select.a('data-href-remove') + '/' + link.a('value'), function(response) {

                });
                link.addClass('selectify-loaded');
            }
        });
    });

    function initLinks(block) {
        s('._sjsselect_dropdown li', block).each(function(li) {
            if (!li.hasClass('selectify-loaded')) {
                li.click(function(li) {
                    s.ajax(select.a('data-href-add') + '/' + li.a('value'), function(response) {

                    });
                    li.addClass('selectify-loaded');
                });
            }
        });
    }
});
