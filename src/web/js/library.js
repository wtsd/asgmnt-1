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
    		console.log('Logout procedure');
		} else if (href == '/add') {
			console.log('New order form');
		} else if (href == '/orders') {
			console.log('Order list');
        }
	});
}