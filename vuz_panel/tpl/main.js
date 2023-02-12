$(document).ready(function () {
    $("#menu a").click(function () {
        $("#menu li.sel").removeClass("sel");
        $(this).parent().addClass("sel");
    });

    showMsg = function (type, title, msg) {
        if ($("#page-msg:visible").size()) {
            $("#page-msg").css("display", "none");
            var data = $(this).data("page-msg");
            clearTimeout(data.handle);
        }

        if (type == 'ok') {
            $("#page-msg span").html(title);
            $("#page-msg p").empty();
            $("#page-msg").removeClass("errMsg").addClass("okMsg");
        } else {
            $("#page-msg span").html(title);
            $("#page-msg p").html(msg);
            $("#page-msg").removeClass("okMsg").addClass("errMsg");
        }

        $("#page-msg").slideDown();
        $("html").animate({scrollTop: $(document).height()}, 'slow');
        var handle = setTimeout(hideMsg, 5000);
        $(this).data("page-msg", {
            handle: handle
        });
    };

    hideMsg = function () {
        $("#page-msg").fadeOut();
    };

    main_page.show();
});