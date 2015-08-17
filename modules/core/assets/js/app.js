$(function () {

    $(".focus-me").focus();

    // Filters
    var $addFilter = $("<a class='button default small'>Add new filter</a>");
    $(".filters .columns.submit").append($addFilter);
    $addFilter.click(function () {
        var filterCount = $(this).parents("form").find(".row.filter").size();
        $lastrow = $(this).parents("form").find(".row.filter:last");
        $newrow = $lastrow.clone();
        $newrow.find("select, input").each(function () {
            var newName = $(this).attr("name").replace(/\[[0-9]+\]/, "[" + filterCount + "]")
            $(this).attr("name", newName);
        });
        $newrow.find(".columns:first").html("&hellip;and");
        $lastrow.after($newrow);
    });

});
