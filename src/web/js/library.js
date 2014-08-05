$.ajaxSetup({
  url: '/ajax',
  type : 'post',
  dataType : 'json',
});

function doLaunch() {
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
        if (href == '/signup') {
        	$.ajax({
                data : {
                    'controller' : 'frmSignup',
                    'values' : values
                },
                success : function (result) {
                    if (result.status == 'ok') {
                        $modal.show().find('.text').html(result.html);
                        signupRoutine();
                    }
                }
            });
        } else if (href == '/login') {
        	$.ajax({
                data : {
                    'controller' : 'frmLogin',
                    'values' : values
                },
                success : function (result) {
                    if (result.status == 'ok') {
                        $modal.show().find('.text').html(result.html);
                        loginRoutine();
                    }
                }
            });
    	} else if (href == '/logout') {
    		$.ajax({
                data : {
                    'controller' : 'doLogout',
                    'values' : values
                },
                success : function (result) {
                    if (result.status == 'ok') {
                        $modal.show().find('.text').html(result.html);
                        $('.role').html(' ');
                        $('.account').html(' ');
                        $('nav ul').html(result.menu);
                        $('#content').html('&nbsp;');
                        doLaunch();
                    }
                }
            });
		} else if (href == '/add') {
			console.log('New order form');
		} else if (href == '/orders') {
			console.log('Order list');
        }
	});
}

function signupRoutine() {
	$('.frmSignup').on('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var login = $('.username').val(),
            pass = $('.pass').val(),
            $modal = $('.modal'),
            role = $('.role option:checked').val(),
            values = {"user": login, "pass": pass, "role":role};


        $.ajax({
            data : {
                'controller' : 'doSignup',
                'values' : values
            },
            beforeSend: function () {
                // @todo: Freeze form
            },
            success : function (result) {

                if (result.status == 'ok') {
                    $modal.find('.text').html(result.html);
                    setTimeout(function () {$modal.hide();}, 1000);
                    
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
                setTimeout(function () {$modal.hide();}, 1000);

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

}

function lstOrdersStart() {

}

function lstOrdersRoutine() {

}

