main_page = {
	show:function() {
		$.ajax({
	        type: 'POST',
	        url: 'index.php',
	        data: {
	            act:'main_page'
	        },
	        success: function(ans) {
	            $('#content').html(ans);
	            $('.hint').hints();
	        }
		});
	}
};