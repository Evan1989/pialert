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
            4: {sorter: false},
        },
        textExtraction: _getValue
    });
}
///////////////////////////////////////////////




///////////////  Dashboard   //////////////////
// noinspection JSUnusedGlobalSymbols
function initJavascriptForDashboard() {
    initBlockSystem( 1, 'updateInputFieldInAlertGroupTable' );
    updateNewAlertFlagCount();
    $('.alert-group select, .alert-group textarea').each(function() {
        addAjaxFunctionForInputInAlertGroupTable($(this));
    });
    $('.tablesorter.alert-group').tablesorter({
        headers: {
            2: {sorter: false},
        },
        textExtraction: _getValue,
        sortList: [[5, 1]]
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
    $('#showHistoryAlerts').change(function(){
        let search = $('#mainTableSearch').val()
        if ( $(this).is(':checked') ) {
            location.href = 'dashboard.php?filter=1&search='+search;
        } else {
            location.href = 'dashboard.php?search='+search;
        }
    });
    setTimeout(function(){
        dashboardPageReload();
    }, 300000);
}
function dashboardPageReload() {
    let search = $('#mainTableSearch').val()
    location.href = 'dashboard.php?search='+search;
}
function addAjaxFunctionForInputInAlertGroupTable(field) {
    field.change(function(){
        changeInputFieldInAlertGroupTable(field);
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
            field.replaceWith(data);
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
            field.replaceWith(data);
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
        $('#modal_alertsForGroup .modal-body').html( data );
        $('#modal_alertsForGroup').modal('show');
        $('#modal_alertsForGroup [data-toggle=\"tooltip\"]').tooltip();
    });
}
// noinspection JSUnusedGlobalSymbols
function loadAlertsForGroup(group_id) {
    $.get( 'dashboard.php?loadAlertsForGroup='+group_id, function( data ) {
        $('#modal_alertsForGroup .modal-body').html( data );
        $('#modal_alertsForGroup').modal('show');
        $('.tablesorter.alert-table').tablesorter( {
            headers: {}
        });
        $('#modal_alertsForGroup [data-toggle=\"tooltip\"]').tooltip();
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
            $('#modal_alertsForGroup .modal-body').html( data );
            $('#modal_alertsForGroup').modal('show');
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
    if ( bell_fill > 0 ) {
        $('.new-alert-count .count').html(bell_fill+bell);
        $('.new-alert-count .bell').hide();
        $('.new-alert-count').removeClass('d-none');
        $(document).prop('title', "🔔 "+PAGE_TITLE);
    } else if ( bell > 0 ) {
        $('.new-alert-count .count').html(bell_fill+bell);
        $('.new-alert-count .bell').show();
        $('.new-alert-count .bell-fill').hide();
        $('.new-alert-count').removeClass('d-none');
        $(document).prop('title', PAGE_TITLE);
    } else {
        $('.new-alert-count').hide();
        $(document).prop('title', PAGE_TITLE);
    }
}
// noinspection JSUnusedGlobalSymbols
function unionAlertGroup(group_id) {
    $.get( 'dashboard.php?unionAlertGroup='+group_id, function( data ) {
        $('#modal_alertsForGroup .modal-body').html( data );
        $('#modal_alertsForGroup').modal('show');
        $('#modal_alertsForGroup [data-toggle=\"tooltip\"]').tooltip();
    });
}
// noinspection JSUnusedGlobalSymbols
function unionAlertGroupStep2(group_id_from, group_id_to) {
    $.get( 'dashboard.php?unionAlertGroupStep2='+group_id_from+'&group_id_to='+group_id_to, function( data ) {
        $('#modal_alertsForGroup .modal-body').html( data );
        $('#modal_alertsForGroup').modal('show');
    });
}
function getValueForFilterTr(tr) {
    let result;
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