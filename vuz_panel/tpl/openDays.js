openDays = {
    show:function() {
        $('#content').html('<table id="openDaysGrid"></table>')
        	.append('<div id="gridOverlay"></div>')
        	.append('<div id="page-msg"><span></span><p></p></div>');
        $("#openDaysGrid").flexigrid({
            url: 'index.php?act=openDaysShow',
            dataType: 'json',
            singleSelect: true,
            showToggleBtn: false,
            onDblClick: function () {
        		$(".fbutton .edit").parent().parent().click();
        	},
            colModel : [
            	{display: 'Краткое описание', name : 'name', width : '40%', sortable : false, align: 'left'},
                {display: 'Вуз или подразделение', name : 'vuz', width : '60%', sortable : false, align: 'left'},
                {display: 'Время и дата', name : 'address', width : 110, sortable : false, align: 'center'}
            ],
            buttons : [
                {name: 'Добавить', bclass: 'add', onpress : function(button,grid){
                	$("#gridOverlay").gridOverlay({
                		grid: grid,
                		url: 'index.php',
                		query: {
                			act: 'openDayAddForm'
                		},
                		onShow: function() {
                			$(".cancel").click(function(){
            	            	$("#gridOverlay").fadeOut('normal',function(){
            	            		$(grid).slideDown();
            	            	});
            	            	return(false);
            	            });
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
                			
            	            if($("#type").size()) {
            	            	$("#type").dropDown({
            	            		width:150,
            	            		onSelect: function(val) {
            	            			if(val=="sv") {
            	            				if(!$("#svDD").size()) {
            	            					$("#sv").dropDown({
            	            	            		width:310,
            	            	            		id:"svDD"
            	            	            	});
            	            				}
            	            				$("#sv").parent().slideDown();
            	            			}
            	            			else {
            	            				$("#sv").parent().slideUp();
            	            			}
            	            		}
            	            	});
            	            	$("#sv").parent().css("display","none");
            	            }
            	         
            	            $("#date").datepicker({
            	            	dateFormat: 'dd.mm.yy',
            					changeYear: true
            				});

            	            $("#online").change(function () {
								if($(this).is(":checked")) {
									$("#address").attr('disabled', 'disabled');
                                    $("#full-addr").hide();
								} else {
                                    $("#address").removeAttr('disabled');
                                    $("#full-addr").show();
								}
                            });
            	            $("#address").keyup(function() {
            	            	$("#fa_address").html($(this).val());
            	            });
            	            
            	            $('#openDayFrm').validateFields();
            				$("#openDayFrm").ajaxForm({
            		            type: 'POST',
            		            url: 'index.php',
            				    data: {
            						act:'openDayAdd'
            				    },
            				    beforeSubmit:function() {
                                    if(!$("#online").is(":checked")) {
                                        if(!$("#address").val()) {
                                            showMsg("err","Некоторые поля заполнены некорректно", "Необходимо указать адрес мероприятия");
                                            return(false);
                                        }

                                        if($("#address").val().indexOf($("#locat").html()) != -1) {
                                            showMsg("err","Некоторые поля заполнены некорректно", "Указание населенного пункта в адресе запрещено");
                                            return(false);
                                        }
									}

            		            	if($('#name').val()) {
                                        if(!/[A-ZА-ЯЁ]/.test($('#name').val())) {
                                            showMsg("err","Не все поля заполнены корректно", "Название не содержит ни одной заглавной буквы");
                                            return(false);
                                        }
                                        if($('#name').val().match(/[A-ZА-ЯЁ]/g).length > 5) {
                                            showMsg("err","Не все поля заполнены корректно", "Слишком много заглавных букв в названии");
                                            return(false);
                                        }
									}

            				    	if($('#openDayFrm').validateForm()) {
            				    		showMsg("err","Не все поля заполнены корректно", "Соответстующее поле отменчено красной рамкой");
            				    		return(false);
            				    	}
            				    	if($('#hours').val()>23) {
            				    		$('#hours').addClass("badField");
            				    		showMsg("err", "Ошибка заполнения", "Некорректное время начала мероприятия");
            				    		return(false);
            				    	}
            				    	if($('#mins').val()>59) {
            				    		$('#mins').addClass("badField");
            				    		showMsg("err", "Ошибка заполнения", "Некорректное время начала мероприятия");
            				    		return(false);
            				    	}
            				    	$("#openDayFrm #buttons button[type='submit']").html("Загрузка...");
            	                    $("#openDayFrm #buttons button").attr("disabled","disabled");
            				    },
            				    success:function(msg) {
            						$("#openDayFrm #buttons button[type='submit']").html("Добавить");
            						$("#openDayFrm #buttons button").removeAttr("disabled");
            						if(msg=='success') {
            							$('#openDaysGrid').flexReload();
            							$("#gridOverlay").fadeOut('normal',function(){
                    	            		$(grid).slideDown();
                    	            	});
            						}
            						else {
            							showMsg("err","Не удалось добавить мероприятие", msg);
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
	                			act: 'openDayEditForm'
	                		},
	                		onShow: function() {
	                			$(".cancel").click(function(){
	            	            	$("#gridOverlay").fadeOut('normal',function(){
	            	            		$(grid).slideDown();
	            	            	});
	            	            	return(false);
	            	            });
	                			
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
	                			if($("#type").size()) {
	            	            	$("#type").dropDown({
	            	            		width:150,
	            	            		onSelect: function(val) {
	            	            			if(val=="sv") {
	            	            				if(!$("#svDD").size()) {
	            	            					$("#sv").dropDown({
	            	            	            		width:310,
	            	            	            		id:"svDD"
	            	            	            	});
	            	            				}
	            	            				$("#sv").parent().slideDown();
	            	            			}
	            	            			else {
	            	            				$("#sv").parent().slideUp();
	            	            			}
	            	            		}
	            	            	});
	            	            	if($("#type").val()=="vuz") {
	            	            		$("#sv").parent().css("display","none");
	            	            	}
	            	            	else {
	            	            		$("#sv").dropDown({
    	            	            		width:310,
    	            	            		id:"svDD"
    	            	            	});
	            	            	}
	                			}
	            	            
	            	            $("#date").datepicker({
	            	            	dateFormat: 'dd.mm.yy',
	            					changeYear: true
	            				});
                                $("#online").change(function () {
                                    if($(this).is(":checked")) {
                                        $("#address").attr('disabled', 'disabled');
                                        $("#full-addr").hide();
                                    } else {
                                        $("#address").removeAttr('disabled');
                                        $("#full-addr").show();
                                    }
                                });
                                $("#online").change();
	            	            $("#address").keyup(function() {
	            	            	$("#fa_address").html($(this).val());
	            	            });
	            	            
	            	            $('#openDayFrm').validateFields();
	            				$("#openDayFrm").ajaxForm({
	            		            type: 'POST',
	            		            url: 'index.php',
	            				    data: {
	            						act:'openDayEdit'
	            				    },
	            				    beforeSubmit:function() {
                                        if(!$("#online").is(":checked")) {
                                            if(!$("#address").val()) {
                                                showMsg("err","Некоторые поля заполнены некорректно", "Необходимо указать адрес мероприятия");
                                                return(false);
                                            }

                                            if($("#address").val().indexOf($("#locat").html()) != -1) {
                                                showMsg("err","Некоторые поля заполнены некорректно", "Указание населенного пункта в адресе запрещено");
                                                return(false);
                                            }
                                        }

                                        if($('#name').val()) {
                                            if(!/[A-ZА-ЯЁ]/.test($('#name').val())) {
                                                showMsg("err","Не все поля заполнены корректно", "Название не содержит ни одной заглавной буквы");
                                                return(false);
                                            }
                                            if($('#name').val().match(/[A-ZА-ЯЁ]/g).length > 5) {
                                                showMsg("err","Не все поля заполнены корректно", "Слишком много заглавных букв в названии");
                                                return(false);
                                            }
                                        }

	            				    	if($('#openDayFrm').validateForm()) {
	            				    		showMsg("err","Не все поля заполнены корректно", "Соответстующее поле отменчено красной рамкой");
	            				    		return(false);
	            				    	}
	            				    	if($('#hours').val()>23) {
	            				    		$('#hours').addClass("badField");
	            				    		showMsg("err", "Ошибка заполнения", "Некорректное время начала мероприятия");
	            				    		return(false);
	            				    	}
	            				    	if($('#mins').val()>59) {
	            				    		$('#mins').addClass("badField");
	            				    		showMsg("err", "Ошибка заполнения", "Некорректное время начала мероприятия");
	            				    		return(false);
	            				    	}
	            				    	$("#openDayFrm #buttons button[type='submit']").html("Загрузка...");
	            	                    $("#openDayFrm #buttons button").attr("disabled","disabled");
	            				    },
	            				    success:function(msg) {
	            						$("#openDayFrm #buttons button[type='submit']").html("Сохранить");
	            						$("#openDayFrm #buttons button").removeAttr("disabled");
	            						if(msg=='success') {
	            							$('#openDaysGrid').flexReload();
	            							$("#gridOverlay").fadeOut('normal',function(){
	                    	            		$(grid).slideDown();
	                    	            	});
	            						}
	            						else {
	            							showMsg("err","Не удалось сохранить мероприятие", msg);
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
	                		content: "Вы уверены в том, что хотите удалить день открытых дверей?",
	                		onConfirm: function() {
		                		$.ajax({
	                                type: "POST",
	                                url: "index.php",
	                                data: {
	                                    act:'openDayDel',
	                                    id: $('.trSelected',grid)[0].id.substr(3)
	                                },
	                                success: function(msg){
	                                    if(msg=='success') {
	                                    	$('#openDaysGrid').flexReload(); 
	                                    }
	                                    else {
	                                    	showMsg("err","Не удалось удалить день открытых дверей", msg);
	                                    }
	                                }
	                            });
	                		}
	                	});
                	}
                }}
            ],
            usepager: true,
            title: 'Управление днями открытых дверей',
            useRp: false,
            height: 366
        });
    }
};