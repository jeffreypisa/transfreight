(function($) {
  $( document ).ready(function() {
    function footerdown() {
      var fo = $("footer").height();
      var he = $("header").height();
      var dohi = $(window).height();
      var minhe = dohi - fo - he - 35;
      $("#content").css("min-height", minhe);
    }

    footerdown();

    $( window ).resize(function() {
      footerdown();
    });
    
  });
}(jQuery));