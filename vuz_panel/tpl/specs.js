specs = {
    show: function () {
        $('#content').html('<table id="specsGrid"></table>')
            .append('<div id="gridOverlay"></div>')
            .append('<div id="page-msg"><span></span><p></p></div>');

        $("#specsGrid").flexigrid({
            url: 'index.php?act=specsShow',
            dataType: 'json',
            singleSelect: true,
            //showToggleBtn: false,
            onDblClick: function () {
                $(".fbutton .edit").parent().parent().click();
            },
            colModel: [
                {display: 'id', name: 'id', width: 50, sortable: true, align: 'center'},
                {display: 'Код', name: 'code', width: 55, sortable: true, align: 'center'},
                {display: 'Название специальности', name: 'name', width: '35%', sortable: true, align: 'left'},
                {display: 'Профиль', name: 'profile', width: '35%', sortable: true, align: 'left'},
                {display: 'Уровень', name: 'level', width: 90, sortable: true, align: 'center'},
                {display: 'Форма', name: 'form', width: 85, sortable: true, align: 'center'},
                {display: 'Подразделение', name: 'subvuz', width: '30%', sortable: true, align: 'left'},
                {
                    display: 'Акт.',
                    name: 'actual',
                    width: 28,
                    sortable: false,
                    align: 'center',
                    process: function (cell, id) {
                        if ($(cell).html() == "Нет") {
                            $(cell).css('color', '#e00');
                            if (!$("#page-msg.errMsg").size()) {
                                $("#page-msg span").html('У вашего вуза есть неактуальные образовательные программы');
                                $("#page-msg p").html('Неактуальными образовательными программами являются программы, данные о которых не были подтверждены в контрольные сроки установленные правилами проекта. Для подтверждения актуальности данных образовательной программы необходимо войти в режим ее редактирования, скорректировать данные если это необходимо и нажать кнопку "Cохранить".');
                                $("#page-msg").addClass("errMsg").show();
                            }
                        }
                    }
                }
            ],
            buttons: [
                {
                    name: 'Добавить', bclass: 'add', onpress: function (button, grid) {
                        if ($("#page-msg").is(":visible")) {
                            $("#page-msg").hide();
                        }
                        $("#gridOverlay").gridOverlay({
                            grid: grid,
                            url: 'index.php',
                            query: {
                                act: 'specAddForm'
                            },
                            afterShow: function () {
                                $('#code').focus();
                            },
                            onShow: function () {
                                $(".hint").hints();
                                $("input[type='text'][id!='code']").keydown(function (e) {
                                    if (e.which == 13) {
                                        return (false);
                                    }
                                });
                                $('#code').keydown(function (e) {
                                    if (e.which == 13) {
                                        $('#findCode').click();
                                        return (false);
                                    }
                                    if ((e.which < 37 || e.which > 40) && $("#advFields").is(":visible")) {
                                        if ($('#findCode').is(':disabled')) {
                                            $('#findCode').removeAttr('disabled');
                                        }
                                        $("#name").html('');
                                        $("#advFields, #buttons").fadeOut();
                                    }
                                })
                                    .inputmask("99.99.99");
                                $('#findCode').click(function (e) {
                                    e.preventDefault();
                                    $('#findCode').attr("disabled", "disabled");
                                    $("#advFields").empty();

                                    if (!/\d{2,2}\.\d{2,2}\.\d{2,2}/g.test($('#code').val())) {
                                        $("#name").html('Введите шестизначный цифровой код направления подготовки');
                                        $("#code").val('');
                                        return (false);
                                    }

                                    var code = $('#code').val().replace(/\./g, '');
                                    $.ajax({
                                        type: 'POST',
                                        url: 'index.php',
                                        data: {
                                            act: 'findOksoCode',
                                            code: code
                                        },
                                        success: function (data) {
                                            $('#findCode').removeAttr('disabled');
                                            if (data == 'bad') {
                                                $("#code").val('');
                                                $("#name").html('Такого кода нет в нашей таблице направлений подготовки');
                                                $("#advFields").fadeOut();
                                            } else {
                                                $("#advFields").append(data);
                                                switch (code.substr(3, 1)) {
                                                    case '3':
                                                        $("#name").html($("#spec-name").val() + ' — Бакалавриат');
                                                        break;
                                                    case '5':
                                                        $("#name").html($("#spec-name").val() + ' — Специалитет');
                                                        break;
                                                    case '4':
                                                        $("#name").html($("#spec-name").val() + ' — Магистратура');
                                                        $("#exams").remove();
                                                        break;
                                                    case '6':
                                                        $("#name").html($("#spec-name").val() + ' — Аспирантура');
                                                        $("#exams").remove();
                                                        break;
                                                }
                                                specs.doForm(data);
                                            }
                                            return (false);
                                        }
                                    });
                                });

                                $(".cancel").click(function () {
                                    $("#gridOverlay").fadeOut('normal', function () {
                                        $(grid).slideDown();
                                    });
                                    return (false);
                                });

                                $('#specFrm').validateFields();
                                $("#specFrm").ajaxForm({
                                    type: 'POST',
                                    url: 'index.php',
                                    data: {
                                        act: 'specAdd'
                                    },
                                    beforeSubmit: function () {
                                        if (($(".subHeader").size() && $("#f").is(":checked")) || !$(".subHeader").size()) {
                                            if (!parseInt($('#free').val()) && !parseInt($('#f_cost').val())) {
                                                showMsg("err", "Ошибка заполнения", "Укажите количество бюджетных мест или стоимость обучения для первого высшего");
                                                return (false);
                                            }
                                        }

                                        if ($(".subHeader").size()) {
                                            var f, s = 0;
                                            if ($("#f").is(":checked")) {
                                                f = 1;

                                                if (!$("#fscore").is(':disabled') && $("#fscore").val() && !parseInt($("#free").val())) {
                                                    showMsg("err", "Ошибка заполнения", "Указан проходной балл, но нет бюджетных мест");
                                                    return (false);
                                                }

                                                if (!$("#pscore").is(':disabled') && $("#pscore").val() && !parseInt($("#f_cost").val())) {
                                                    showMsg("err", "Ошибка заполнения", "Указан проходной балл, но нет стоимости обучения");
                                                    return (false);
                                                }

                                                // Exams doubles
                                                var i;
                                                $("#exams div[id!='example'] select").each(function () {
                                                    i = 0;
                                                    var t = this;
                                                    if ($(t).val() != "0") {
                                                        $("#exams div[id!='example'] select").each(function () {
                                                            if (t != this) {
                                                                if ($(t).val() == $(this).val()) {
                                                                    i = 1;
                                                                    showMsg("err", "Ошибка заполнения", "Нельзя добавить два одинаковых экзамена");
                                                                    return (false);
                                                                }
                                                            }
                                                        });
                                                        if (i) {
                                                            return (false);
                                                        }
                                                    }
                                                });
                                                if (i) {
                                                    return (false);
                                                }
                                            }
                                            if ($("#s").is(":checked")) {
                                                s = 1;
                                                if (!parseInt($('#s_cost').val())) {
                                                    $("#s_cost").addClass('badField');
                                                    showMsg("err", "Ошибка заполнения", "Укажите стоимость обучения для коммерческих мест второго высшего");
                                                    return (false);
                                                }
                                            }

                                            if (!f && !s) {
                                                showMsg("err", "Ошибка заполнения", "Выберите первое или(и) второе высшее образование");
                                                return (false);
                                            }
                                        }

                                        $('#specFrm').validateFields();
                                        if ($('#specFrm').validateForm()) {
                                            showMsg("err", "Не все поля заполнены корректно", "Соответстующее поле отменчено красной рамкой");
                                            return (false);
                                        }

                                        $("#specFrm #buttons button[type='submit']").html("Загрузка...");
                                        $("#specFrm #buttons button").attr("disabled", "disabled");
                                    },
                                    success: function (msg) {
                                        $("#specFrm #buttons button[type='submit']").html("Добавить");
                                        $("#specFrm #buttons button").removeAttr("disabled");
                                        if (msg == 'success') {
                                            $('#specsGrid').flexReload();
                                            $("#gridOverlay").fadeOut('normal', function () {
                                                $(grid).slideDown();
                                            });
                                        } else {
                                            showMsg("err", "Не удалось добавить направление подготовки", msg);
                                        }
                                    }
                                });
                            }
                        });
                    }
                },
                {
                    name: 'Редактировать', bclass: 'edit', onpress: function (com, grid) {
                        if ($("#page-msg").is(":visible")) {
                            $("#page-msg").hide();
                        }
                        if ($('.trSelected', grid).size()) {
                            var id = $('.trSelected', grid)[0].id.substr(3);
                            $("#gridOverlay").gridOverlay({
                                grid: grid,
                                url: 'index.php',
                                query: {
                                    id: id,
                                    act: 'specEditForm'
                                },
                                onShow: function () {
                                    if ($("#lvl").val() == 4) {
                                        $('#exams').parent().remove();
                                    }

                                    $("input[type='text']").keydown(function (e) {
                                        if (e.which == 13) {
                                            return (false);
                                        }
                                    });

                                    specs.doForm();

                                    if ($("#fscore").length) {
                                        if (!$("#fscore").val()) {
                                            $("#no-fscore").attr("checked", "checked").change();
                                        }

                                        if (!$("#pscore").val()) {
                                            $("#no-pscore").attr("checked", "checked").change();
                                        }
                                    }
                                    //$("#fscore, #pscore").change();


                                    $(".cancel").click(function () {
                                        $("#gridOverlay").fadeOut('normal', function () {
                                            $(grid).slideDown();
                                        });
                                        return (false);
                                    });

                                    $('#specFrm').validateFields();
                                    $("#specFrm").ajaxForm({
                                        type: 'POST',
                                        url: 'index.php',
                                        data: {
                                            act: 'specEdit'
                                        },
                                        beforeSubmit: function () {
                                            if (($(".subHeader").size() && $("#f").is(":checked")) || !$(".subHeader").size()) {
                                                if (!parseInt($('#free').val()) && !parseInt($('#f_cost').val())) {
                                                    showMsg("err", "Ошибка заполнения", "Укажите количество бюджетных мест или стоимость обучения для первого высшего");
                                                    return (false);
                                                }
                                            }

                                            if ($(".subHeader").size()) {
                                                var f, s = 0;
                                                if ($("#f").is(":checked")) {
                                                    f = 1;

                                                    if (!$("#fscore").is(':disabled') && $("#fscore").val() && !parseInt($("#free").val())) {
                                                        showMsg("err", "Ошибка заполнения", "Указан проходной балл, но нет бюджетных мест");
                                                        return (false);
                                                    }

                                                    if (!$("#pscore").is(':disabled') && $("#pscore").val() && !parseInt($("#f_cost").val())) {
                                                        showMsg("err", "Ошибка заполнения", "Указан проходной балл, но нет стоимости обучения");
                                                        return (false);
                                                    }

                                                    // Exams doubles
                                                    var i;
                                                    $("#exams div[id!='example'] select").each(function () {
                                                        i = 0;
                                                        var t = this;
                                                        if ($(t).val() != "0") {
                                                            $("#exams div[id!='example'] select").each(function () {
                                                                if (t != this) {
                                                                    if ($(t).val() == $(this).val()) {
                                                                        i = 1;
                                                                        showMsg("err", "Ошибка заполнения", "Нельзя добавить два одинаковых экзамена");
                                                                        return (false);
                                                                    }
                                                                }
                                                            });
                                                            if (i) {
                                                                return (false);
                                                            }
                                                        }
                                                    });
                                                    if (i) {
                                                        return (false);
                                                    }
                                                }
                                                if ($("#s").is(":checked")) {
                                                    s = 1;
                                                    if (!parseInt($('#s_cost').val())) {
                                                        $("#s_cost").addClass('badField');
                                                        showMsg("err", "Ошибка заполнения", "Укажите стоимость обучения для коммерческих мест второго высшего");
                                                        return (false);
                                                    }
                                                }

                                                if (!f && !s) {
                                                    showMsg("err", "Ошибка заполнения", "Выберите первое или(и) второе высшее образование");
                                                    return (false);
                                                }
                                            }

                                            $('#specFrm').validateFields();
                                            if ($('#specFrm').validateForm()) {
                                                showMsg("err", "Не все поля заполнены корректно", "Соответстующее поле отменчено красной рамкой");
                                                return (false);
                                            }

                                            $("#specFrm #buttons button[type='submit']").html("Загрузка...");
                                            $("#specFrm #buttons button").attr("disabled", "disabled");
                                        },
                                        success: function (msg) {
                                            $("#specFrm #buttons button[type='submit']").html("Сохранить");
                                            $("#specFrm #buttons button").removeAttr("disabled");
                                            if (msg == 'success') {
                                                $('#specsGrid').flexReload();
                                                $("#gridOverlay").fadeOut('normal', function () {
                                                    $(grid).slideDown();
                                                });
                                            } else {
                                                showMsg("err", "Не удалось сохранить направление подготовки", msg);
                                            }
                                        }
                                    });
                                }
                            });
                        }
                    }
                },
                {
                    name: 'Удалить', bclass: 'delete', onpress: function (button, grid) {
                        if ($('.trSelected', grid).size()) {
                            $.window({
                                type: "confirm",
                                winId: "delWin",
                                content: "Вы уверены в том, что хотите удалить данную образовательную программу?",
                                onConfirm: function () {
                                    $.ajax({
                                        type: "POST",
                                        url: "index.php",
                                        data: {
                                            act: 'specDel',
                                            id: $('.trSelected', grid)[0].id.substr(3)
                                        },
                                        success: function (msg) {
                                            if (msg == 'success') {
                                                $('#specsGrid').flexReload();
                                            } else {
                                                showMsg("err", "Удалить образовательную программу не удалось", msg);
                                            }
                                        }
                                    });
                                }
                            });
                        }
                    }
                },
                {
                    name: 'Клонировать', bclass: 'clone', onpress: function (button, grid) {
                        if ($('.trSelected', grid).size()) {
                            $.ajax({
                                type: "POST",
                                url: "index.php",
                                data: {
                                    act: 'specClone',
                                    id: $('.trSelected', grid)[0].id.substr(3)
                                },
                                success: function (msg) {
                                    if (msg == 'success') {
                                        $('#specsGrid').flexReload();
                                    } else {
                                        showMsg("err", "Копировать образовательную программу не удалось", msg);
                                    }
                                }
                            });
                        }
                    }
                }
            ],
            searchitems: [
                {display: 'Код', name: 'code', isdefault: true},
                {display: 'Название', name: 'name', isdefault: false},
                {display: 'Профиль', name: 'profile', isdefault: false},
                {display: 'Подразделение', name: 'subvuz', isdefault: false}
            ],
            sortname: "code",
            sortorder: "asc",
            usepager: true,
            title: 'Управление образовательными программами',
            useRp: false,
            height: 350
        });
    },
    doForm: function () {
        $(".hint2").hints();

        $('#form, #srok').dropDown({
            width: 150
        });
        $('#sv').dropDown({
            width: 600
        });

        $("#f_cost").priceFormat({
            centsLimit: 0,
            thousandsSeparator: ' ',
            prefix: ''
        });
        $("textarea[name=f_text], textarea[name=s_text]").keyup(function () {
            if ($(this).val().length > 300) {
                $(this).val($(this).val().slice(0, 300));
            }
            $(this).closest(".examsTxt").find('b').html($(this).val().length);
        }).keyup();

        if ($(".subHeader").size()) {
            $(".subHeader .leftCol input[type='checkbox']").change(function () {
                if ($(this).is(":checked")) {
                    $(".fs .leftCol").removeClass("disabled");
                    $(".fs .leftCol input, .fs .leftCol textarea, .fs .leftCol select").removeAttr("disabled", "disabled");
                } else {
                    $(".fs .leftCol").addClass("disabled");
                    $(".fs .leftCol input, .fs .leftCol textarea, .fs .leftCol select").attr("disabled", "disabled");
                }
            });

            $(".subHeader .rightCol input[type='checkbox'").change(function () {
                if ($(this).is(":checked")) {
                    $(".fs .rightCol").removeClass("disabled");
                    $(".fs .rightCol input, .fs .rightCol textarea, .fs .rightCol select").removeAttr("disabled", "disabled");
                } else {
                    $(".fs .rightCol").addClass("disabled");
                    $(".fs .rightCol input, .fs .rightCol textarea, .fs .rightCol select").attr("disabled", "disabled");
                }
            });
            $(".subHeader .leftCol input[type='checkbox']").change();
            $(".subHeader .rightCol input[type='checkbox'").change();

            $("#no-fscore").change(function () {
                if ($(this).is(":checked")) {
                    $("#fscore").attr("disabled", "disabled");
                    $("#fscore").hide();
                } else {
                    $("#fscore").removeAttr("disabled");
                    $("#fscore").show();
                }
            });
            $("#no-pscore").change(function () {
                if ($(this).is(":checked")) {
                    $("#pscore").attr("disabled", "disabled");
                    $("#pscore").hide();
                } else {
                    $("#pscore").removeAttr("disabled");
                    $("#pscore").show();
                }
            });


            $("#s_cost").click(function () {
                if (parseInt($("#f_cost").val()) && !parseInt($("#s_cost").val())) {
                    $("#s_cost").val($("#f_cost").val());
                }
            });
            $("#s_cost").priceFormat({
                centsLimit: 0,
                thousandsSeparator: ' ',
                prefix: ''
            });
        } else { /* mag */
            if ($("#lang").size()) {
                $("#lang").dropDown({
                    width: 300
                });
            }
        }

        if ($("#addEge").size()) {
            $("#addEge").click(function () {
                var $exam = $("#example").clone();
                $exam.removeAttr('id');
                $exam.appendTo($("#example").parent());
            });
            if ($('.exam').size() == 1) {
                $("#addEge").click();
            }

            if ($(".subHeader").size()) {
                $(".subHeader .chbLabel").change();
            }
        }
        $("#advFields, #buttons").fadeIn();
    },
    delEge: function (t) {
        $(t).parent().remove();
        if ($('.exam').size() == 1) {
            $("#egeHead").fadeOut('fast');
        }
        return (false);
    },
    set_selectable: function (t) {
        if ($(t).is(":checked")) {
            $(t).prev().val('1');
        } else {
            $(t).prev().val('0');
        }
    },
    delProfile: function (t) {
        $(t).parent().remove();
        return (false);
    }
};