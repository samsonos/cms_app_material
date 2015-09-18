/* Form main JS */

s('.material-structure-selectify').pageInit(function(select) {
    select.selectify();

    initLinks(select.prev());


    s('._sjsselect_dropdown li', select.prev()).each(function(li) {
        if (!li.hasClass('selectify-loaded')) {
            li.click(function(li) {
                s.ajax(select.a('data-href-add') + '/' + li.a('value'), function(response) {
                    initLinks(select.prev());
                });
                li.addClass('selectify-loaded');
            });
        }
    });

    function initLinks(block) {
        s('._sjsselect ._sjsselect_delete', block).each(function(link) {
            if (!link.hasClass('selectify-loaded')) {
                link.click(function(link) {
                    s.ajax(select.a('data-href-remove') + '/' + link.a('value'), function(response) {
                    });
                    link.addClass('selectify-loaded');
                });
            }
        });
    }

});
