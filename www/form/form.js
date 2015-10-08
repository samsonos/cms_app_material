/* Form main JS */

SamsonCMS_InputINIT_SELECT_STRUCTURE = function(select) {
    select.selectify();

    initLinks(select.prev());

    s('._sjsselect_dropdown li', select.prev()).each(function(li) {
        if (!li.hasClass('selectify-loaded')) {
            li.click(function(li) {
                s.ajax(select.a('data-href-add') + '/' + li.a('value'), function(response) {
                    initLinks(select.prev());
                    updateSelect();
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
                        time = Date.now();
                        updateSelect();
                    });
                    link.addClass('selectify-loaded');
                });
            }
        });
    }
};

// Bind input
SamsonCMS_Input.bind(SamsonCMS_InputINIT_SELECT_STRUCTURE, '.material-structure-selectify');

// Global params
var timer = 0;
var instance = false;
var timeout = 500; // 1 sec

// If update select with structures then update main form
function updateSelect(){

    // If there started some instance of this function then not call any instances
    if ((instance != false)){
        return;
    }
    instance = true;

    // Set timeout and call loading form
    setTimeout(function(){
        loader.show('', true);
        var changeBlock = s('.application-form');
        var url = s('.material-structure-selectify').a('data-update-form');
        s.ajax(url, function(response) {
            if (response != null) {
                var data = JSON.parse(response);
                if (data.form != null) {
                    // Change hash to main
                    location.hash = '#samsoncms_form_tab_Generic';
                    changeBlock.html(data.form);
                    // Init all tabs
                    SamsonCMS_Input.update(s('body'));
                    loader.hide();
                    instance = false;
                }
            }
        });
    }, timeout);
}
