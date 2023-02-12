vuzAbout = {
    general: function () {
        $.ajax({
            type: 'POST',
            url: 'index.php',
            data: {
                act: 'vuzGenForm'
            },
            success: function (ans) {
                $('#content').html(ans);

                $(".hint").hints();

                $('#gos').dropDown({
                    width: 165
                });

                $("#address").keyup(function () {
                    $("#fa_address").html($(this).val());
                });
                $("#index").keyup(function () {
                    $("#fa_index").html($(this).val());
                });

                vuzAbout.initPhones();

                $('#genFrm').validateFields();
                $("#genFrm").ajaxForm({
                    type: 'POST',
                    url: 'index.php',
                    data: {
                        act: 'vuzGenEdit'
                    },
                    beforeSubmit: function () {
                        if ($("#address").val().indexOf($("#locat").html()) != -1) {
                            showMsg("err", "Некоторые поля заполнены корректно", "Указание населенного пункта в адресе запрещено");
                            return (false);
                        }
                        if ($(".telCode").val()) {
                            if (!/^\d{3,5}$/.test($(".telCode").val())) {
                                $(".telCode").addClass("badField");
                            }
                            if (!/^\d{1,3}$/.test($(".tel1").val())) {
                                $(".tel1").addClass("badField");
                            }
                            if (!/^\d{2,2}$/.test($(".tel2").val())) {
                                $(".tel2").addClass("badField");
                            }
                            if (!/^\d{2,2}$/.test($(".tel3").val())) {
                                $(".tel3").addClass("badField");
                            }
                        }

                        if ($('#genFrm').validateForm()) {
                            showMsg("err", "Не все поля заполнены корректно", "Красным помечены поля которые следует скорректировать");
                            return (false);
                        } else {
                            $("#genFrm button").html("Загрузка...");
                            $("#genFrm button").attr("disabled", "disabled");
                        }
                    },
                    success: function (msg) {
                        $("#genFrm button").html("Сохранить изменения");
                        $("#genFrm button").removeAttr("disabled");
                        if (msg == 'success') {
                            showMsg("ok", "Информация успешно сохранена", null);
                        } else {
                            showMsg("err", "Не удалось сохранить изменения", msg);
                        }
                    }
                });
            }
        });
    },
    adv: function () {
        $.ajax({
            type: 'POST',
            url: 'index.php',
            data: {
                act: 'vuzAdvForm'
            },
            success: function (ans) {
                $('#content').html(ans);

                $(".hint").hints();

                $('#advFrm select').dropDown({
                    width: 80
                });
                $('#advFrm textarea').change(function () {
                    $("#textEdit").val('1');
                });
                $("#advFrm").ajaxForm({
                    url: 'index.php',
                    data: {
                        act: 'vuzAdvEdit'
                    },
                    beforeSubmit: function () {
                        var file;
                        if (file = $("#logoFile").val()) {
                            if (!(/\.jpg$/i.test(file)) && !(/\.png$/i.test(file))) {
                                var $parent = $("#logoFile").parent();
                                $("#logoFile").remove();
                                $parent.append('<input type="file" id="logoFile" name="logoFile" />');
                                showMsg("err", "Не удалось сохранить изменения", "Разрешено загружать только файлы в формате jpg и png");
                                return (false);
                            }
                            var size = 0;
                            if ($.browser.msie) {
                                try {
                                    var myFSO = new ActiveXObject("Scripting.FileSystemObject");
                                    file = myFSO.getFile(document.getElementById("logoFile").value);
                                    size = file.size;
                                } catch (e) {
                                }
                            } else {
                                try {
                                    file = $("#logoFile")[0];
                                    size = file.files[0].size;
                                } catch (e) {
                                }
                            }
                            if (size > 30720) {
                                var $parent = $("#logoFile").parent();
                                $("#logoFile").remove();
                                $parent.append('<input type="file" id="logoFile" name="logoFile" />');
                                showMsg("err", "Не удалось сохранить изменения", "Превышен максимальный размер логотипа (30 кб)");
                                return (false);
                            }
                            $("#advFrm button").html("Загрузка...");
                            $("#advFrm button").attr("disabled", "disabled");
                        }

                        if (file = $("#galleryFile").val()) {
                            if (!(/\.jpg$/i.test(file))) {
                                var $parent = $("#galleryFile").parent();
                                $("#galleryFile").remove();
                                $parent.append('<input type="file" id="galleryFile" name="galleryFile" />');
                                showMsg("err", "Не удалось сохранить изменения", "Разрешено загружать только файлы в формате jpg");
                                return (false);
                            }
                            var size = 0;
                            if ($.browser.msie) {
                                try {
                                    var myFSO = new ActiveXObject("Scripting.FileSystemObject");
                                    file = myFSO.getFile(document.getElementById("galleryFile").value);
                                    size = file.size;
                                } catch (e) {
                                }
                            } else {
                                try {
                                    file = $("#galleryFile")[0];
                                    size = file.files[0].size;
                                } catch (e) {
                                }
                            }
                            if (size > 1048576) {
                                var $parent = $("#galleryFile").parent();
                                $("#galleryFile").remove();
                                $parent.append('<input type="file" id="galleryFile" name="galleryFile" />');
                                showMsg("err", "Не удалось сохранить изменения", "Превышен максимальный размер");
                                return (false);
                            }
                            $("#advFrm button").html("Загрузка...");
                            $("#advFrm button").attr("disabled", "disabled");
                        }
                    },
                    success: function (msg) {
                        $("#advFrm button").html("Сохранить изменения");
                        $("#advFrm button").removeAttr("disabled");
                        if (msg.length > 1000) {
                            showMsg("err", "Не удалось сохранить изменения", "Превышен размер загружаемого файла или загрузка продолжалась более 3 минут");
                        } else {
                            msg = msg.replace(/<\/?[^>]+>/gi, '');
                            if (msg == "success") {
                                if ($("#logoFile").val()) {
                                    var $parent = $("#logoFile").parent();
                                    $("#logoFile").remove();
                                    $parent.append('<input type="file" id="logoFile" name="logoFile" />');
                                    $("fieldset span.logo").html("Новый логотип загружен");
                                }
                                if ($("#galleryFile").val()) {
                                    var $parent = $("#galleryFile").parent();
                                    $("#galleryFile").remove();
                                    $parent.append('<input type="file" id="galleryFile" name="galleryFile" />');
                                    $("fieldset span.gallery").html("Новое фото загружено");
                                }
                                if ($("#textEdit").val() == '1') {
                                    $('textarea').parent().prev().remove();
                                    $('textarea').parent().remove();
                                    $("#textEdit").val('0');
                                }
                                showMsg("ok", "Информация успешно сохранена", null);
                            } else {
                                showMsg("err", "Не удалось сохранить изменения", msg);
                            }
                        }
                    }
                });
            }
        });
    },
    lic: function () {
        $.ajax({
            type: 'POST',
            url: 'index.php',
            data: {
                act: 'vuzLicForm'
            },
            success: function (ans) {
                $('#content').html(ans);

                if ($("#parent-lic.active").size()) {
                    $("#parent-lic.active").css("display", "block");
                    $("#licFrm div").first().remove();
                }

                $(".date").datepicker({
                    defaultDate: 0,
                    changeYear: true
                });

                $("#no-acr").click(function () {
                    $("#acr_num, #acr_start, #acr_end").val('');
                    $("#acr_num").keyup();
                    return (false);
                });

                $("#acr_num").keyup(function () {
                    if ($(this).val()) {
                        $("#accr-alert").hide();
                    } else {
                        $("#accr-alert").show();
                    }
                });
                $("#acr_num").keyup();

                if ($("#lic_end").val() == 'Бессрочно') {
                    $("#lic_unlim").attr("checked", "checked");
                    $("#lic_end").css("display", "none");
                }
                $("#lic_unlim").change(function () {
                    if ($("#lic_unlim").is(":checked")) {
                        $("#lic_end").css("display", "none");
                    } else {
                        $("#lic_end").val("").css("display", "inline-block");
                    }
                });

                $('#licFrm').validateFields();
                $("#licFrm").ajaxForm({
                    url: "index.php",
                    data: {
                        act: "vuzLicEdit"
                    },
                    beforeSubmit: function () {
                        if ($('#licFrm').validateForm()) {
                            showMsg("err", "Не все поля заполнены корректно", "Красным помечены поля которые следует скорректировать");
                            return (false);
                        } else {
                            $("#licFrm button").html("Загрузка...");
                            $("#licFrm button").attr("disabled", "disabled");
                        }
                    },
                    success: function (msg) {
                        $("#licFrm button").html("Сохранить изменения");
                        $("#licFrm button").removeAttr("disabled");

                        if (msg == "success") {
                            showMsg("ok", "Данные успешно сохранены", null);
                        } else {
                            showMsg("err", "Не удалось сохранить изменения", msg);
                        }
                    }
                });
            }
        });
    },
    priem: function () {
        $.ajax({
            type: 'POST',
            url: 'index.php',
            data: {
                act: 'vuzPriemForm'
            },
            success: function (ans) {
                $('#content').html(ans);
                $("#index").keyup(function () {
                    $("#fa_index").html($(this).val());
                });
                $("#address").keyup(function () {
                    $("#fa_address").html($(this).val());
                });
                $(".hint").hints();
                $("#copy").click(function () {
                    $.ajax({
                        type: 'POST',
                        url: 'index.php',
                        data: {
                            act: 'getVuzAddr'
                        },
                        success: function (data) {
                            data = data.split('|');
                            $("#index").val(data[0]);
                            $("#addr").val(data[1]);
                            $("#addr, #index").keyup();
                        }
                    });
                });

                $(".schedule select").dropDown({
                    width: 50
                });

                if ($('.schedule tbody tr').size() > 2) {
                    $("#addSchedule").remove();
                } else {
                    $("#addSchedule").click(function () {
                        if ($('.schedule tbody tr').size() == 2) {
                            $(this).remove();
                        }
                        if ($('.schedule tbody tr').size() < 3) {
                            var t = $('tbody tr').first().clone();
                            $('.cb', t).removeAttr("checked");
                            for (var i = 0; i < 7; i++) {
                                $('tbody tr').each(function () {
                                    if (!$('.cb', t).eq(i).is(":checked")) {
                                        if ($(".cb", this).eq(i).is(":checked")) {
                                            $('.cb', t).eq(i).attr("disabled", "disabled");
                                        }
                                    }
                                });
                            }

                            $(".adv, .pdays", t).val('');
                            $(".pdays", t).val('0000000');
                            $('tbody').append(t);
                            $('.enwDropDown', t).remove();
                            $('select', t).removeAttr('ddOptId').dropDown({width: 50});

                            scheduleCb();
                        }
                        return (false);
                    });
                }

                scheduleCb = function () {
                    $(".schedule .cb").change(function () {
                        i = $(this).parent().index();
                        if ($(this).is(":checked")) {
                            $(".schedule tbody tr").each(function () {
                                $(".cb", this).eq(i).attr("disabled", "disabled");
                            });
                            $(this).removeAttr('disabled').attr("checked", "checked");
                        } else {
                            release = true; //onload OR onclick
                            $(".schedule tbody tr").each(function () {
                                if ($(".cb", this).eq(i).is(":checked") && release) {
                                    release = false;
                                }
                            });

                            if (release) {
                                $(".schedule tbody tr").each(function () {
                                    $(".cb", this).eq(i).removeAttr("disabled");
                                });
                            }
                        }

                        var p = $(this).closest('tr');
                        var hash = '';

                        $('.cb', p).each(function () {
                            if ($(this).is(":checked")) {
                                hash += '1';
                            } else {
                                hash += '0';
                            }
                        });

                        $(".pdays", p).val(hash);
                    });
                };
                scheduleCb();
                $(".schedule .cb").change();

                $("#start, #end").datepicker({
                    changeYear: true,
                    dateFormat: 'dd.mm.yy'
                });
                $("#allyear").change(function () {
                    if ($(this).is(":checked")) {
                        $("#start, #end").attr("disabled", "disabled").val('');
                    } else {
                        $("#start, #end").removeAttr("disabled");
                    }
                }).change();

                $("#addTel").click(function () {
                    if ($(".tel").size() == 2) {
                        $("#addTel").attr("disabled", "disabled");
                    }

                    var tmp = $("#telExample").clone().removeAttr('id');
                    $("label, .hint", tmp).remove();
                    $("input[rel]", tmp).attr("rel", "*natural").removeClass("badField");
                    $("input", tmp).val('');
                    $("#tels").append(tmp);

                    vuzAbout.initPhones();
                });
                vuzAbout.initPhones();

                if ($(".tel").size() == 3) {
                    $("#addTel").attr("disabled", "disabled");
                }

                $("#priemFrm").validateFields();
                $("#priemFrm").ajaxForm({
                    type: 'POST',
                    url: 'index.php',
                    data: {
                        act: 'vuzPriemEdit'
                    },
                    beforeSubmit: function () {
                        if ($("#addr").val().indexOf($("#locat").html()) != -1) {
                            showMsg("err", "Некоторые поля заполнены корректно", "Указание населенного пункта в адресе запрещено");
                            return (false);
                        }

                        if ($("#index").val().length != 6) {
                            $('#index').addClass("badField");
                            showMsg("err", "Почтовый индекс должен состоять из 6 цифр", null);
                            return (false);
                        }
                        $(".tel").each(function () {
                            if ($(".telCode", this).val()) {
                                if (!/^\d{3,5}$/.test($(".telCode", this).val())) {
                                    $(".telCode", this).addClass("badField");
                                }
                                if (!/^\d{1,3}$/.test($(".tel1", this).val())) {
                                    $(".tel1", this).addClass("badField");
                                }
                                if (!/^\d{2,2}$/.test($(".tel2", this).val())) {
                                    $(".tel2", this).addClass("badField");
                                }
                                if (!/^\d{2,2}$/.test($(".tel3", this).val())) {
                                    $(".tel3", this).addClass("badField");
                                }
                                if (($(".telCode", this).val().length + $(".tel1", this).val().length + $(".tel2", this).val().length + $(".tel3", this).val().length) > 10) {
                                    showMsg("err", "Телефон не может состоять более чем из 10 цифр", null);
                                    return (false);
                                }
                            }
                        });

                        if (!$("#allyear").is(":checked")) {
                            var reg = /^\d{2,2}\.\d{2,2}\.\d{4,4}$/;
                            if (!reg.test($("#start").val()) || !reg.test($("#end").val())) {
                                showMsg("err", "Не указан период работы приемной комиссии", null);
                                return (false);
                            }
                        }

                        t = true;
                        $(".schedule .cb").each(function () {
                            if ($(this).is(":checked")) {
                                t = false;
                                return (false);
                            }
                        });
                        if (t) {
                            showMsg("err", "Не указан график работы приемной комиссии", null);
                            return (false);
                        }
                        if ($('#priemFrm').validateForm()) {
                            showMsg("err", "Не все поля заполнены корректно", null);
                            return (false);
                        } else {
                            $("#priemFrm button").html("Загрузка...");
                            $("#priemFrm button").attr("disabled", "disabled");
                        }
                    },
                    success: function (msg) {
                        $("#priemFrm button").html("Сохранить изменения");
                        $("#priemFrm button").removeAttr("disabled");
                        if (msg == 'success') {
                            showMsg("ok", "Информация успешно сохранена", null);
                        } else {
                            showMsg("err", "Не удалось сохранить изменения", msg);
                        }
                    }
                });
            }
        });
    },
    initPhones: function () {
        var tmp;
        $(".telCode").keyup(function () {
            if (($(this).val().length + $(".tel1", $(this).parent()).val().length) > 6) {
                $(this).val($(this).val().slice(0, -1));
            }
            if ($(this).val().length == 5) {
                $(".tel1", $(this).parent()).focus();
            }
        });
        $(".tel1").keyup(function () {
            tmp = $(".telCode", $(this).parent()).val().length;
            if (($(this).val().length + tmp) > 6) {
                $(this).val($(this).val().slice(0, -1));
            }
            if (tmp == 3) {
                if ($(this).val().length == 3) {
                    $(".tel2", $(this).parent()).focus();
                }
            } else {
                if (tmp == 4) {
                    if ($(this).val().length == 2) {
                        $(".tel2", $(this).parent()).focus();
                    }
                } else {
                    if (tmp == 5) {
                        if ($(this).val().length == 1) {
                            $(".tel2", $(this).parent()).focus();
                        }
                    }
                }
            }
        });
        $(".tel2").keyup(function () {
            if ($(this).val().length == 2) {
                $(".tel3", $(this).parent()).focus();
            }
        });
    }
};