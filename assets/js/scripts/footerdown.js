(function($) {
  $( document ).ready(function() {
    // Matchheight

    function footerdown() {
      var fo = $(".footer").outerHeight();
      var he = $("header").height();
      var dohi = $(window).height();
      var minhe = dohi - fo - he;
      $("#content").css("min-height", minhe);
    }

    footerdown();
    

    $( window ).resize(function() {
      footerdown();
    });
  });
}(jQuery));