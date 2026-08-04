$(document).ready(function() {
    $('.dropdown-item.active').closest('.nav-item').find('.nav-link').addClass('active');
    $('[data-toggle=\"tooltip\"]').tooltip();

    let mainTableSearch = $('#mainTableSearch');
    if ( mainTableSearch.length > 0 ) {
        mainTableSearch.on('keyup', function () {
            executeSearchFilter($(this));
        });
        executeSearchFilter(mainTableSearch);
    }

    $('#auth-token').each(function(){
        if ( $(this).val() ) {
            $('#auth-form').submit();
        }
    })

});
function executeSearchFilter(input) {
    let value = input.val().toLowerCase();
    $('.main-table-for-filter tbody tr').filter(function () {
        $(this).toggle(getValueForFilterTr($(this)).toLowerCase().indexOf(value) > -1)
    });
}

// получить значение ячейки таблицы при сортировке
// noinspection JSUnusedGlobalSymbols
let _getValue = function(node) {
    let child0 = node.childNodes[0];
    // input и select
    if ( child0 && (child0.tagName === 'INPUT' || child0.tagName === 'SELECT') ) {
        return child0.value;
    }
    // ссылки
    if ( child0 && (child0.tagName === 'A' || child0.tagName === 'SPAN') ) {
        return node.childNodes[0].innerHTML;
    }
    // картинки
    if ( child0 && child0.tagName === 'IMG' ) {
        return '';
    }
    // просто текст
    return node.innerHTML
}

function executeFunction( element, param ) {
    if ( element ) {
        if (typeof window[element] == 'function') {
            if ( param === undefined ) {
                return window[element]();
            } else {
                return window[element]( param );
            }
        } else if (typeof element == 'function') {
            if ( param === undefined ) {
                return element();
            } else {
                return element( param );
            }
        }
        return false;
    }
    return true;
}

// noinspection JSUnusedGlobalSymbols
function loadSystemAbout() {
    $.ajax({
        type: 'POST',
        url: location.href,
        data: {'ajaxSystemAbout':true},
        'success': function(data) {
            $('#modal_piAlertDefault .modal-body').html( data );
            $('#modal_piAlertDefault').modal('show');
        }
    });
}


function br2nl(str, replaceMode) {
    let replaceStr = (replaceMode) ? "\n" : '';
    // Includes <br>, <BR>, <br />, </br>
    return str.replace(/<\s*\/?br\s*\/?>/gi, replaceStr);
}




//////////  Block input system   //////////////
const BLOCK_SYSTEM_TIME_INTERVAL = 1000;
let BLOCK_SYSTEM_INITIALIZED = false;
/**
 * Инициализация системы блокировок frontend для многопользовательского режима работы, надо вызвать один раз на старте страницы
 * @param menu_id
 * @param callback_function функция, которую надо вызвать после снятия блокировки
 */
function initBlockSystem( menu_id, callback_function ) {
    if ( BLOCK_SYSTEM_INITIALIZED ) {
        return;
    }
    setInterval(function(){
        checkBlockForPages(menu_id, callback_function);
    }, BLOCK_SYSTEM_TIME_INTERVAL);
    BLOCK_SYSTEM_INITIALIZED = true;
}
function addBlockSystemToElement(field, menu_id) {
    let id = field.attr('id');
    field.focusin(function(){
        field.addClass('block-by-this-browser');
        $.get( 'util/locks.php?action=create&menu_id='+menu_id+'&element_id='+id, function( data ) {
            if ( data === 'true' ) {
                field.focusout(function(){
                    setTimeout(function(){
                        $.get('util/locks.php?action=delete&menu_id='+menu_id+'&element_id='+id);
                        field.removeClass('block-by-this-browser');
                    }, BLOCK_SYSTEM_TIME_INTERVAL);
                });
            } else {
                field.attr('disabled', 'disabled');
            }
        });
    })
}
/**
 * Метод для загрузки из backend списка блокировок
 * @param menu_id параметр для запроса к backend
 * @param callback_function функция, которую надо вызвать после снятия блокировки
 */
function checkBlockForPages(menu_id, callback_function) {
    $.get( 'util/locks.php?action=check&menu_id='+menu_id, function( data ) {
        let oldLocks = [];
        if (callback_function) {
            $('.block-by-block-system').each(function(){
                oldLocks.push($(this).attr('id'));
            });
        }
        let blocks = $.parseJSON(data);
        $(blocks).each(function (index, block) {
            // noinspection JSUnusedLocalSymbols
            $.each(block, function (element_id, user_id) {
                if ( callback_function ) {
                    let oldLockIndex = oldLocks.indexOf(element_id);
                    if (oldLockIndex>=0) {
                        oldLocks.splice(oldLockIndex, 1);
                    }
                }
                $('#' + element_id).not('.block-by-this-browser').prop('disabled', true).addClass('block-by-block-system');
            });
        });
        if (callback_function) {
            oldLocks.map(function(element_id){
                let field = $('#'+element_id);
                field.prop('disabled', false).removeClass('block-by-block-system');
                executeFunction(callback_function, field);
            });
        }
    });
}
///////////////////////////////////////////////




//////////////      Profile     ///////////////
// noinspection JSUnusedGlobalSymbols
function initJavascriptForProfile() {
    $('.profile-change-language').change(function(){
        let language = $(this).val()
        location.href = 'profile.php?newLanguage='+language;
    });
    $('.profile-change-avatar').change(function(){
        let inputField = $(this);
        let avatar = inputField.val()
        inputField.prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: 'profile.php',
            data: {'newAvatar': avatar},
            'success': function() {
                inputField.prop('disabled', false);
            }
        });
    });
}
///////////////////////////////////////////////




///////////////   Mass Alerts  ////////////////
// noinspection JSUnusedGlobalSymbols
function initJavascriptForMassAlerts() {
    $('.mass-alert-help-button-system').click(function(){
        $("form input[name='system']").val( $(this).html() );
    });
    $('.mass-alert-help-button-error').click(function(){
        $("form textarea[name='errorText']").val( $(this).html() );
    });
}
///////////////////////////////////////////////




///////////////      Users     ////////////////
// noinspection JSUnusedGlobalSymbols
function initJavascriptForUsers() {
    initBlockSystem( 4, false );
    $('.admin-users input').each(function(){
        addBlockSystemToElement($(this), 4);
    }).change(function(){
        $(this).prop('disabled', true);
        let id = $(this).attr('id');
        let value;
        if ( $(this).is(':checkbox') ) {
            if ( $(this).is(':checked') ) {
                value = 1;
            } else {
                value = 0;
            }
        } else {
            value = $(this).prop('value');
        }
        $.ajax({
            type: 'POST',
            url: 'users.php',
            data: {'field':id, 'value':value},
            'success': function(data) {
                $('#'+id).prop('disabled', false);
                console.log(data);
            }
        });
    });
    $('.tablesorter').tablesorter( {
        headers: {
            3: {sorter: false},
            5: {sorter: false},
        },
        textExtraction: _getValue
    });
}
///////////////////////////////////////////////




///////////////  Dashboard   //////////////////
// noinspection JSUnusedGlobalSymbols
function ajaxUploadDashboardPages(page) {
    $('.page-ajax-load-loader').html('Loading page '+page);
    let url = getDashboardPageURL()+'&page='+page;
    $.ajax({
        type: 'GET',
        url: url,
        xhrFields: {
            withCredentials: true
        },
        'success': function(data) {
            $('.page-ajax-load-data').append(data);
        }
    });
}
function ajaxUploadDashboardPagesFinish() {
    let loader = $('.page-ajax-load-loader');
    loader.html('Javascript initialization');
    setTimeout(function() {
        initJavascriptForDashboardStep2();
        loader.html('');
    }, 250);
}
function initJavascriptForDashboardStep1() {
    $('#showOnlyNewAlerts').change(function(){
        dashboardPageReload();
    });
    $('#showOnlyImportant').change(function(){
        dashboardPageReload();
    });
}
function initJavascriptForDashboardStep2() {
    initBlockSystem( 1, 'updateInputFieldInAlertGroupTable' );
    updateNewAlertFlagCount();

    let editFields = $('.alert-group select, .alert-group textarea');
    let editFieldsCount = editFields.length;
    let num = 0;
    editFields.each(function() {
        addAjaxFunctionForInputInAlertGroupTable($(this));
        num++;
        if ( num % 400 === 0 ) {
            console.log( Math.ceil(100*num / editFieldsCount) + '%' )
        }
    });

    $('.new-alert-count').click(function(){
        let scrollTo = $('.new-alert-flag.bell-fill:visible').last();
        if ( scrollTo.length === 0 ) {
            scrollTo = $('.new-alert-flag:visible').last();
        }
        $([document.documentElement, document.body]).animate({
            scrollTop: scrollTo.offset().top - scrollTo.parents('tr').height()
        }, 200);
    });
    setInterval(function(){
        dashboardPageReload();
    }, 300000);
    getSystemContact();
}
let dashboardPageURL = '';
function getDashboardPageURL() {
    let url = 'dashboard.php?'
    let group_id = $('#getFilterGroupID');
    if ( group_id.length > 0 && group_id.val() > 0 ) {
        dashboardPageURL = url+'id='+group_id.val();
        return dashboardPageURL;
    }
    if ( !$('#showOnlyNewAlerts').is(':checked') ) {
        url = url+'showHistoryAlerts=1&';
    }
    if ( !$('#showOnlyImportant').is(':checked') ) {
        url = url+'showNotImportant=1&';
    }
    let search = $('#mainTableSearch').val()
    dashboardPageURL = url+'search='+search;
    return dashboardPageURL;
}
let reloadBlocked = false;
function dashboardPageReload() {
    if ( reloadBlocked ) {
        return;
    }
    location.href = getDashboardPageURL();
}
function addAjaxFunctionForInputInAlertGroupTable(field) {
    field.change(function(){
        changeInputFieldInAlertGroupTable(field);
    }).focusin(function(){
        reloadBlocked = true;
    }).focusout(function(){
        reloadBlocked = false;
    });
    if ( field.is('textarea') ) {
        addHTMLDivInsteadOfTextArea(field)
    }
    addBlockSystemToElement(field, 1);
}
function addHTMLDivInsteadOfTextArea(textarea) {
    let parent = textarea.parent();
    let div = parent.children('.alert-group-comment-html-div');
    if ( div.length > 0 ) {
        if ( div.length > 1 ) {
            // Кусок кода на случай, если поле поменял другой пользователь
            // то мы второй раз через ajax подтянули новый div, старый надо удалить
            div.slice(1).remove();
        }
        // если место позволяет, то сделаем div побольше планового
        if ( parent.height() > div.height() + 24 ) {
            div.height( 24*Math.floor(parent.height()/24)+12 );
        }
        div.click(function(event){
            // если клик был по ссылке, то просто переходим, иначе убираем наш div и показываем textarea
            if ( $(event.target).is('a') === false ) {
                div.remove();
                textarea.removeClass('d-none').focus();
            }
        });
    } else {
        textarea.removeClass('d-none');
    }
}
function updateInputFieldInAlertGroupTable(field) {
    field.prop('disabled', true);
    let id = field.attr('id');
    $.ajax({
        type: 'POST',
        url: 'dashboard.php',
        data: {'element':id},
        'success': function(data) {
            field.parent().html(data);
            addAjaxFunctionForInputInAlertGroupTable($('#'+id));
        }
    });
}
function changeInputFieldInAlertGroupTable(field) {
    field.prop('disabled', true);
    let id = field.attr('id');
    let value = field.val();
    let userFieldUpdated = field.hasClass('alert-group-user-select');
    let statusFieldUpdated = field.hasClass('alert-group-status-select');
    $.ajax({
        type: 'POST',
        url: 'dashboard.php',
        data: {'element':id, 'value':value},
        'success': function(data) {
            field.parent().html(data);
            let updatedField = $('#'+id)
            if ( userFieldUpdated === true ) {
                updateInputFieldInAlertGroupTable(updatedField.parent().parent().find('.alert-group-status-select'));
            }
            if ( statusFieldUpdated === true ) {
                updateInputFieldInAlertGroupTable(updatedField.parent().parent().find('.alert-group-user-select'));
                let temp = id.split('_');
                checkAlertGroupAsComplete(temp[1]);
            }
            addAjaxFunctionForInputInAlertGroupTable(updatedField);
        }
    });
}
// noinspection JSUnusedGlobalSymbols
function loadAlertGroupFullInfo(group_id) {
    $.get( 'dashboard.php?loadAlertGroupFullInfo='+group_id, function( data ) {
        $('#modal_piAlertDefault .modal-body').html( data );
        $('#modal_piAlertDefault').modal('show');
        $('#modal_piAlertDefault [data-toggle=\"tooltip\"]').tooltip();
    });
}
// noinspection JSUnusedGlobalSymbols
function loadAlertsForGroup(group_id) {
    $.get( 'dashboard.php?loadAlertsForGroup='+group_id, function( data ) {
        $('#modal_piAlertDefault .modal-body').html( data );
        $('#modal_piAlertDefault').modal('show');
        $('.tablesorter.alert-table').tablesorter( {
            headers: {}
        });
        $('#modal_piAlertDefault [data-toggle=\"tooltip\"]').tooltip();
    });
}
// noinspection JSUnusedGlobalSymbols
function checkAlertGroupAsComplete(group_id) {
    $.get( 'dashboard.php?checkAlertGroupAsComplete='+group_id, function( data ) {
        if ( data === 'true' ) {
            $('#checkAlertGroupAsCompleteLink_'+group_id).hide();
            updateNewAlertFlagCount();
            updateInputFieldInAlertGroupTable($('#status_'+group_id))
        } else {
            $('#modal_piAlertDefault .modal-body').html( data );
            $('#modal_piAlertDefault').modal('show');
        }
    });
}
let PAGE_TITLE = '';
function updateNewAlertFlagCount() {
    if ( PAGE_TITLE === '' ) {
        PAGE_TITLE = $(document).prop('title');
    }
    let bell = $('.new-alert-flag.bell:visible').length;
    let bell_fill = $('.new-alert-flag.bell-fill:visible').length;
    if ( bell_fill > 0 || showNoAlertWarningBadge_count > 0 ) {
        $('.new-alert-count .count').html(bell_fill+bell);
        $('.new-alert-count .bell').hide();
        $('.new-alert-count').removeClass('d-none');
        $(document).prop('title', "🔔 "+PAGE_TITLE);
        $('#favicon').attr('href', '/favicon.png?v2');
    } else if ( bell > 0 ) {
        $('.new-alert-count .count').html(bell_fill+bell);
        $('.new-alert-count .bell').show();
        $('.new-alert-count .bell-fill').hide();
        $('.new-alert-count').removeClass('d-none');
        $(document).prop('title', PAGE_TITLE);
        $('#favicon').attr('href', '/favicon2.png?v2');
    } else {
        $('.new-alert-count').hide();
        $(document).prop('title', PAGE_TITLE);
        $('#favicon').attr('href', '/favicon2.png?v2');
    }
}
// noinspection JSUnusedGlobalSymbols
function unionAlertGroup(group_id) {
    $.get( 'dashboard.php?unionAlertGroup='+group_id, function( data ) {
        $('#modal_piAlertDefault .modal-body').html( data );
        $('#modal_piAlertDefault').modal('show');
        $('#modal_piAlertDefault [data-toggle=\"tooltip\"]').tooltip();
    });
}
// noinspection JSUnusedGlobalSymbols
function unionAlertGroupStep2(group_id_from, group_id_to) {
    $.get( 'dashboard.php?unionAlertGroupStep2='+group_id_from+'&group_id_to='+group_id_to, function( data ) {
        $('#modal_piAlertDefault .modal-body').html( data );
        $('#modal_piAlertDefault').modal('show');
    });
}
function getValueForFilterTr(tr) {
    let result = tr.attr('filter-value');
    tr.find('td').each(function(){
        result += getValueForFilterTd($(this));
    });
    return result;
}
function getValueForFilterTd(td) {
    let input = td.find('input:first-child, textarea:first-child');
    if ( input.length > 0 ) {
        return input.val();
    }
    input = td.find('select:first-child');
    if ( input.length > 0 ) {
        return input.find("option:selected").text();
    }
    return td.text();
}
let showNoAlertWarningBadge_count = 0;
// noinspection JSUnusedGlobalSymbols
function showNoAlertWarningBadge(system) {
    showNoAlertWarningBadge_count++;
    if ( showNoAlertWarningBadge_count === 1 ) {
        $('.no-alert-warning').removeClass('d-none').children('span').html(system);
    } else {
        $('.no-alert-warning span').first().clone().html(system).appendTo('.no-alert-warning');
    }
}
///////////////////////////////////////////////






//////////////      Online     ////////////////
// noinspection JSUnusedGlobalSymbols
function initJavascriptForOnline() {
    $('.tablesorter').tablesorter( {
        textExtraction: _getValue,
        sortList: [[0, 0]]
    });
}

///////////////////////////////////////////////





//////////////      Systems   ////////////////
// noinspection JSUnusedGlobalSymbols
function initJavascriptForSystems(document) {
    $('.tablesorter').tablesorter({
        textExtraction: _getValue
    });

    let table = document.getElementById('systems');
    console.log("call selectSystemInfo");
    for (let i = 1; i < table.rows.length; i++) {
        table.rows[i].onclick = function () {
           let rIndex = this.rowIndex;
           document.getElementById('code').value = document.getElementById("systems").rows[rIndex].cells[1].innerHTML;
           $('#prev_code').val( document.getElementById('code').value );
           document.getElementById('name').value = document.getElementById("systems").rows[rIndex].cells[2].innerHTML;
           document.getElementById('contact').value = br2nl(document.getElementById("systems").rows[rIndex].cells[3].innerHTML);
           document.getElementById('comment').value = br2nl(document.getElementById("systems").rows[rIndex].cells[4].innerHTML);

        };
    }
}

function getSystemContact() {
    $('.system_contact').on('mouseover',function(){
        let _this =$(this);
        $.ajax({
            type:'post',
            url:'dashboard.php',
            data:{code : _this.html()},
            success: function(json){
                if ( json ) {
                    let data = JSON.parse(json)
                    _this.popover({
                        container: 'body',
                        html: true,
                        title: data.name,
                        content: data.contact
                    }).popover('show');
                }
            }
        });
    }).on('mouseleave', function () {
        closePopupWithSystemContactLater( $(this) )
    });
}
function closePopupWithSystemContactLater(element) {
    let popupID = element.attr('aria-describedby');
    let interval = setInterval(function () {
        if (!$('#' + popupID + ':hover').length) {
            element.popover('hide');
            clearInterval(interval);
        }
    }, 500);
}
///////////////////////////////////////////////