(function($) {
  $( document ).ready(function() {
    $(".modal").on("show", function () {
      $("body").addClass("modal-open");
    }).on("hidden", function () {
      $("body").removeClass("modal-open");
    });
  });
}(jQuery));