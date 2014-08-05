function doLaunch() {
    $('nav a').on('click', function (e) {
    	e.preventDefault();
        var $item = $(this),
            href = $item.attr('href'),
            $modal = $('.modal'),
            values = '';
        if (href == '/signup') {
        	console.log('Registration form');
        } else if (href == '/login') {
        	console.log('Login form');
    	} else if (href == '/logout') {
    		console.log('Logout procedure');
		} else if (href == '/add') {
			console.log('New order form');
		} else if (href == '/orders') {
			console.log('Order list');
        }
	});
}