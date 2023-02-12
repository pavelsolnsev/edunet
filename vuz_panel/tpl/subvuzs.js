subvuzs = {
    show:function() {
        $('#content').html('<table id="subvuzsGrid"></table>')
        	.append('<div id="gridOverlay"></div>')
        	.append('<div id="page-msg"><span></span><p></p></div>');
        $("#subvuzsGrid").flexigrid({
            url: 'index.php?act=svShow',
            dataType: 'json',
            singleSelect: true,
            showToggleBtn: false,
            resizable: false,
            onDblClick: function () {
        		$(".fbutton .edit").parent().parent().click();
        	},
            colModel : [
                {display: 'Название', name : 'name', width : '45%', sortable : false, align: 'left'},
                {display: 'Адрес', name : 'address', width : '30%', sortable : false, align: 'left'}
            ],
            buttons : [
                {name: 'Добавить', bclass: 'add', onpress : function(button,grid){
                	$("#gridOverlay").gridOverlay({
                		grid: grid,
                		url: 'index.php',
                		query: {
                			act: 'svAddForm'
                		},
                		
                		onShow: function() {
                			$(".hint").hints();
                			$("#copy").click(function() {
                				$.ajax({
                                    type: 'POST',
                                    url: 'index.php',
                                    data: {
                                        act:'getVuzAddr'
                                    },
                                    success:function(data) {
                                    	data=data.split('|');
                                    	$("#address").val(data[1]);
                                    	$("#address").keyup();
                                   	}
                                });
                			});
            				$("input[type='text']").keydown(function(e) {
                             	if(e.which == 13){
                             		return(false);
                             	}
                			});

            	            $("#address").keyup(function() {
            	            	$("#fa_address").html($(this).val());
            	            });

            	            $(".cancel").click(function(){
            	            	$("#gridOverlay").fadeOut('normal',function(){
            	            		$(grid).slideDown();
            	            	});
            	            	return(false);
            	            });
	
							vuzAbout.initPhones();
				
            	            $('#svFrm').validateFields();
            				$("#svFrm").ajaxForm({
            		            type: 'POST',
            		            url: 'index.php',
            				    data: {
            						act:'svAdd'
            				    },
            				    beforeSubmit:function() {
                                    if($("#address").val().indexOf($("#locat").html()) != -1) {
                                        showMsg("err","Некоторые поля заполнены корректно", "Указание населенного пункта в адресе запрещено");
                                        return(false);
									}

            				    	if($('#svFrm').validateForm()) {
            				    		showMsg("err","Некоторые поля заполнены корректно", "Поля заполненные некорректно отмечены красной рамкой");
            				    		return(false);
                        			}
            	                    $("#svFrm #buttons button[type='submit']").html("Загрузка...");
            	                    $("#svFrm button").attr("disabled","disabled");
            				    },
            				    success:function(msg) {
            						$("#svFrm #buttons button[type='submit']").html("Добавить");
            						$("#svFrm button").removeAttr("disabled");
            						if(msg=='success') {
            							$('#subvuzsGrid').flexReload();
            							$("#gridOverlay").fadeOut('normal',function(){
                    	            		$(grid).slideDown();
                    	            	});
            						}
            						else {
            							showMsg("err","Не удалось добавить учебное подразделение", msg);
            						}
            				    }
            				});
                		}
                	});
                }},
                {name: 'Редактировать', bclass: 'edit', onpress : function(com,grid){
                	if($('.trSelected',grid).size()){
						var id = $('.trSelected',grid)[0].id.substr(3);
						$("#gridOverlay").gridOverlay({
	                		grid: grid,
	                		url: 'index.php',
	                		query: {
								id: id,
	                			act: 'svEditForm'
	                		},
	                		onShow: function() {
	                			$(".hint").hints();
	                			$("#copy").click(function() {
	                				$.ajax({
	                                    type: 'POST',
	                                    url: 'index.php',
	                                    data: {
	                                        act:'getVuzAddr'
	                                    },
	                                    success:function(data) {
                                            data=data.split('|');
	                                    	$("#address").val(data[1]);
	                                    	$("#address").keyup();
	                                   	}
	                                });
	                			});
	            				$("input[type='text']").keydown(function(e) {
	                             	if(e.which == 13){
	                             		return(false);
	                             	}
	                			});
	            	            
	             				$("#address").keyup(function() {
	            	            	$("#fa_address").html($(this).val());
	            	            });

	            	            $(".cancel").click(function(){
	            	            	$("#gridOverlay").fadeOut('normal',function(){
	            	            		$(grid).slideDown();
	            	            	});
	            	            	return(false);
	            	            });
	            	            
	            	            $('#svFrm').validateFields();
	            				$("#svFrm").ajaxForm({
	            		            type: 'POST',
	            		            url: 'index.php',
	            				    data: {
	            						act:'svEdit'
	            				    },
	            				    beforeSubmit:function() {
                                        if($("#address").val().indexOf($("#locat").html()) != -1) {
                                            showMsg("err","Некоторые поля заполнены корректно", "Указание населенного пункта в адресе запрещено");
                                            return(false);
                                        }

	            				    	if($('#svFrm').validateForm()) {
	            				    		showMsg("err","Некоторые поля заполнены корректно", "Поля заполненные некорректно отмечены красной рамкой");
	            				    		return(false);
	                        			}
            	                    	$("#svFrm #buttons button[type='submit']").html("Загрузка...");
            	                    	$("#svFrm button").attr("disabled","disabled");
	            				    },
	            				    success:function(msg) {
	            						$("#svFrm #buttons button[type='submit']").html("Сохранить");
	            						$("#svFrm button").removeAttr("disabled");
	            						if(msg=='success') {
	            							$('#subvuzsGrid').flexReload();
	            							$("#gridOverlay").fadeOut('normal',function(){
	                    	            		$(grid).slideDown();
	                    	            	});
	            						}
	            						else {
	            							showMsg("err","Не удалось отредактировать учебное подразделение", msg);
	            						}
	            				    }
	            				});
	                		}
	                	});
				    }
				    else {
				    	return(0);
				    }
                }},
                {name: 'Удалить', bclass: 'delete', onpress : function(button,grid){
                	if($('.trSelected',grid).size()){
	                	$.window({
	                		type: "confirm",
	                		winId: "delWin",
	                		content: "Вы уверены в том, что хотите удалить подразделение?",
	                		onConfirm: function() {
		                		$.ajax({
	                                type: "POST",
	                                url: "index.php",
	                                data: {
	                                    act:'svDel',
	                                    id: $('.trSelected',grid)[0].id.substr(3)
	                                },
	                                success: function(msg){
	                                    if(msg=='success') {
	                                    	$('#subvuzsGrid').flexReload(); 
	                                    }
	                                    else {
	                                    	showMsg("err","Удалить подразделение не удалось", msg);
	                                    }
	                                }
	                            });
	                		}
	                	});
                	}
                }}
            ],
            usepager: true,
            title: 'Управление институтами и факультетами',
            useRp: false,
            height: 366
        });
    }
};