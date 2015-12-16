(function( $ ) {
  $('.civievent-widget-getfields').change( function() {
    if ($(this).val() == '') {
      return true;
    }
    $fill = $(this).next('input');
    existing = $fill.val().replace(/ /g, '');
    existing = existing.split(',');
    existing.push($(this).val());
    existing = _.compact(_.uniq(existing));
    $fill.val(existing.join(', '));
    $(this).val('');
  });
})( jQuery );
