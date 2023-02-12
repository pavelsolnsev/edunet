leads = {
    show:function() {
        $('#content').html('<table id="leadsGrid"></table>')
        	.append('<div id="gridOverlay"></div>')
        	.append('<div id="page-msg"><span></span><p></p></div>');
        $("#leadsGrid").flexigrid({
            url: 'index.php?act=leadsShow',
            dataType: 'json',
            singleSelect: true,
            showToggleBtn: false,
            resizable: false,
            onDblClick: function () {
        		$(".fbutton .edit").parent().parent().click();
        	},
            colModel : [
                {display: '#', name : 'id', width : '5%', sortable : false, align: 'left'},
                {display: 'Имя', name : 'name', width : '15%', sortable : false, align: 'left'},
                {display: 'Телефон', name : 'phone', width : '10%', sortable : false, align: 'left'},
                {display: 'Email', name : 'email', width : '25%', sortable : false, align: 'left'},
                {display: 'Добавлено', name : 'added', width : '15%', sortable : false, align: 'left'},
                {display: 'ВУЗ', name : 'abrev', width : '30%', sortable : false, align: 'left'}
            ],
            usepager: true,
            title: 'Просмотр контактов',
            useRp: false,
            height: 366
        });
    }
};