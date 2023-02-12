thanks={
    show:function() {
        $.ajax({
            type: 'POST',
            url: 'index.php',
            data: {
                act:'thanks'
            },
            success: function(ans) {
            	$('#content').html(ans);
            }
        });
	}
};