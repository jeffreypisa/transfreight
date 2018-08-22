(function($) {
  $( document ).ready(function() {
    $(".js-menu").on("click", function() {
      $("body").toggleClass("menuopen");
      $("body").toggleClass("opensidemenu");
      $(".js-mobilemenu").toggleClass("open");
    });
    
    $(".js-menu-close").on("click", function() {
      $("body").removeClass("menuopen");
      $("body").removeClass("opensidemenu");
      $(".js-mobilemenu").removeClass("open");
    });
    
    $( window ).resize(function() {
      $("body").removeClass("menuopen").removeClass("opensidemenu");
    });
  });
}(jQuery));