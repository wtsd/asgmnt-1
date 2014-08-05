/*jslint browser:true */
/*global $, jQuery, console, CKEDITOR, alert, confirm */
$.ajaxSetup({
    url: '/ajax',
    type : 'post',
    dataType : 'json'
});

function doLaunch() {
    'use strict';
    $('.close').on('click', function (e) {
        e.preventDefault();
        $(this).parents('.modal').hide();
    });
    $('nav a').on('click', function (e) {
        e.preventDefault();
        var $item = $(this),
            href = $item.attr('href'),
            $modal = $('.modal'),
            values = '';
        if (href === '/signup') {
            $.ajax({
                data : {
                    'controller' : 'frmSignup',
                    'values' : values
                },
                success : function (result) {
                    if (result.status === 'ok') {
                        $modal.show().find('.text').html(result.html);
                        signupRoutine();
                    }
                }
            });
        } else if (href === '/login') {
            $.ajax({
                data : {
                    'controller' : 'frmLogin',
                    'values' : values
                },
                success : function (result) {
                    if (result.status === 'ok') {
                        $modal.show().find('.text').html(result.html);
                        loginRoutine();
                    }
                }
            });
        } else if (href === '/logout') {
            $.ajax({
                data : {
                    'controller' : 'doLogout',
                    'values' : values
                },
                success : function (result) {
                    if (result.status === 'ok') {
                        $modal.show().find('.text').html(result.html);
                        $('.role').html(' ');
                        $('.account').html(' ');
                        $('nav ul').html(result.menu);
                        $('#content').html('&nbsp;');
                        doLaunch();
                    }
                }
            });
        } else if (href === '/add') {
            $.ajax({
                data : {
                    'controller' : 'frmOrder',
                    'values' : values
                },
                success : function (result) {
                    if (result.status === 'ok') {
                        $('#content').html(result.html);
                        frmOrderRoutine();
                    }
                }
            });
        } else if (href === '/orders') {
            lstOrdersStart();
        }
    });

    frmOrderRoutine();
    lstOrdersStart();
}

function signupRoutine() {
    'use strict';
    $('.frmSignup').on('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var login = $('.username').val(),
            pass = $('.pass').val(),
            $modal = $('.modal'),
            role = $('.role option:checked').val(),
            values = {"user" : login, "pass" : pass, "role" : role};


        $.ajax({
            data : {
                'controller' : 'doSignup',
                'values' : values
            },
            beforeSend: function () {
                // @todo: Freeze form
            },
            success : function (result) {

                if (result.status === 'ok') {
                    $modal.find('.text').html(result.html);
                    setTimeout(function () { $modal.hide(); }, 1000);
                    
                    $('.role').html(result.role_label + ': ' + result.username);
                    $('.account').html(result.account);
                    $('nav ul').html(result.menu);
                    $('#content').html(result.content);
                    doLaunch();
                } else {
                    alert(result.html);
                }
                
            }
        });
        return false;
    });
}

function loginRoutine() {
    'use strict';
    $('.frmLogin').on('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var login = $('.username').val(),
            pass = $('.pass').val(),
            $modal = $('.modal'),
            values = {"user": login, "pass": pass};


        $.ajax({
            data : {
                'controller' : 'doAuthorize',
                'values' : values
            },
            beforeSend: function () {
                // @todo: Freeze form
            },
            success : function (result) {
                $modal.find('.text').html(result.html);
                setTimeout(function () { $modal.hide(); }, 1000);

                $('.role').html(result.role_label + ': ' + result.username);
                $('.account').html(result.account);
                $('nav ul').html(result.menu);
                $('#content').html(result.content);
                doLaunch();
            }
        });
        return false;
    });
}

function frmOrderRoutine() {
    'use strict';
    $('.frmOrder').on('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var caption = $('.caption').val(),
            descr = $('.descr').val(),
            price = $('.price').val(),
            values = {"caption": caption, "descr": descr, "price": price},
            $modal = $('.modal');


        $.ajax({
            data : {
                'controller' : 'saveOrder',
                'values' : values
            },
            beforeSend: function () {
                // @todo: Freeze form
            },
            success : function (result) {
                if (result.status === 'ok') {
                    $('#content').html(result.msg);
                }
            }
        });
        return false;
    });
}

function lstOrdersStart() {
    'use strict';
    var values = {};
    values.page = 1;
    $.ajax({
        data : {
            'controller' : 'listOrders',
            'values' : values
        },
        success : function (result) {
            //
            if (result.status === 'ok') {
                var contents = '';

                $.each(result.orders, function (index, value) {
                    contents += '<tr><td>' + value.id + '</td><td>' + value.caption + '</td><td>' + value.descr + '</td><td>' + value.price + ' руб.</td><td><button data-id="' + value.id + '" class="exec">Выполнить</button></td></tr>';
                });
                $('#orders tbody').html(contents);
                lstOrdersRoutine();
            } else if (result.status === 'void') {
                $('#orders').hide().after('<p>' + result.msg + '</p>');
            }
        }
    });
}

function lstOrdersRoutine() {
    'use strict';
    $('.exec').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this),
            $tr = $(this).parents('tr'),
            values = {};

        values.order_id = $btn.attr('data-id');

        $.ajax({
            data : {
                'controller' : 'seizeOrder',
                'values' : values
            },
            success : function (result) {
                //
                console.log(result);
                if (result.status === 'ok') {
                    $('.account').html(result.account);
                }
            }
        });

        // @todo: Send request for execution
        console.log($tr);
        $tr.hide();
        //alert($(this).attr('data-id'));
    });
}