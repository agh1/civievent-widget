(function( $ ) {
  $(document).on('ready widget-updated widget-added', function() {
    $('label.civievent-widget-admin-type-label').click( function() {
      $('#' + $(this).attr('for')).click();
    });
    $('input[name="civievent-widget-admin-type"]').change( function() {
      adminTypeSwitch($(this));
    });
    function adminTypeSwitch($field) {
      $field.siblings('label[for!="' + $field.attr('id') + '"]').removeClass('selectedTab');
      $field.siblings('label[for="' + $field.attr('id') + '"]').addClass('selectedTab');
      if ($field.val() == 'custom') {
        $field.siblings('.civievent-widget-admin-custom').show();
        $field.siblings('.civievent-widget-admin-simple').hide();
      }
      else {
        $field.siblings('.civievent-widget-admin-simple').show();
        $field.siblings('.civievent-widget-admin-custom').hide();
      }
    }
    $('.civievent-widget-getfields').change( function() {
      if ($(this).val() == '') {
        return true;
      }
      $fill = $(this).next('input');
      // existing = $fill.val().replace(/ /g, '');
      // existing = existing.split(',');
      // existing.push($(this).val());
      // existing = _.compact(_.uniq(existing));
      // $fill.val(existing.join(', '));
      var existing = {};
      try {
        existing = JSON.parse($fill.val());
      } catch (e) {
        existing = {};
      }
      existing[$(this).val()] = {
        title: 1,
        prefix: null,
        suffix: null,
        wrapper: 1,
      };
      $fill.val(JSON.stringify(existing));
      fillCustomDisplay($fill);

      $(this).val('');
    } );
    $('.civievent-widget-custom-display-params').each( function() {
      fillCustomDisplay($(this));
      if ($(this).val().length > 2) {
        $(this).closest('.civievent-widget-admin-sections').children('input[name="civievent-widget-admin-type"][value="custom"]').click();
      }
      else {
        $(this).closest('.civievent-widget-admin-sections').children('input[name="civievent-widget-admin-type"][value="simple"]').click();
      }
      // var stuff = 'test';
      // $(this).next('.civievent-widget-custom-display-ui').html(stuff);
    } );
    function removefield(field, $item) {
      try {
        existing = JSON.parse($item.val());
      } catch (e) {
        existing = {};
      }
      delete existing[field];
      $item.val(JSON.stringify(existing));
      fillCustomDisplay($item);
    }
    function setFieldValue(field, $item, attrib, val) {
      try {
        existing = JSON.parse($item.val());
      } catch (e) {
        existing = {};
      }
      existing[field][attrib] = val;
      $item.val(JSON.stringify(existing));
      fillCustomDisplay($item);
    }
    function fillCustomDisplay($item) {
      try {
        existing = JSON.parse($item.val());
      } catch (e) {
        existing = {};
      }
      $select = $item.prev('.civievent-widget-getfields');
      $ui = $item.siblings('.civievent-widget-custom-display-ui');
      $ui.html('');
      for (var field in existing) {
        if (existing.hasOwnProperty(field)) {
          var fieldWrapper = document.createElement('span');
          var $fieldui = $('<span/>', {
            class: 'field-custom-ui field-custom-ui-' + field,
            text: $select.find('option[value="' + field + '"]').html(),
            fieldname: field,
          }).appendTo($ui);
          var $displayTitle = $('<input/>', {
            type: 'checkbox',
            name: field + '-title',
          }).change({ field: field, item: $item }, function(event) {
            var isChecked = $(this).prop('checked') ? 1 : 0;
            setFieldValue(event.data.field, event.data.item, 'title', isChecked);
          });
          var $prefix = $('<input/>', {
            type: 'text',
            name: field + '-prefix',
          }).change({ field: field, item: $item }, function(event) {
            setFieldValue(event.data.field, event.data.item, 'prefix', $(this).val());
          });
          var $suffix = $('<input/>', {
            type: 'text',
            name: field + '-suffix',
          }).change({ field: field, item: $item }, function(event) {
            setFieldValue(event.data.field, event.data.item, 'suffix', $(this).val());
          });
          var $displayWrapper = $('<input/>', {
            type: 'checkbox',
            name: field + '-wrapper',
          }).change({ field: field, item: $item }, function(event) {
            var isChecked = $(this).prop('checked') ? 1 : 0;
            setFieldValue(event.data.field, event.data.item, 'wrapper', isChecked);
          });
          var $remove = $('<a/>', {
            class: 'removefield',
            href: '#',
            text: 'Delete',
            onclick: 'return false;',
          }).click({ field: field, item: $item }, function(event) {
            removefield(event.data.field, event.data.item);
          });
          $fieldui.append(
            $('<br/>'),
            $displayTitle,
            $('<label/>', {
              for: field + '-title',
              text: 'Display title?',
            }),
            $('<br/>'),
            $('<label/>', {
              for: field + '-prefix',
              text: 'Prefix:',
            }),
            ' ',
            $prefix,
            $('<br/>'),
            $('<label/>', {
              for: field + '-suffix',
              text: 'Suffix:',
            }),
            ' ',
            $suffix,
            $('<br/>'),
            $displayWrapper,
            $('<label/>', {
              for: field + '-wrapper',
              text: 'Wrap field/title in <span>',
            }),
            $('<br/>'),
            $remove
          );
          // $('<br/>').appendTo($fieldui);
          // $('<input/>', {
          //   type: 'checkbox',
          //   name: field + '-title',
          // }).appendTo($fieldui);
          // $('<label/>', {
          //   for: field + '-title',
          //   text: 'Display title?',
          // }).appendTo($fieldui);
          // $ui.append('<span class="field-custom-ui field-custom-ui-' + field + '"></span>');
          // var $fieldui = $ui.children('.field-custom-ui-' + field);
          // $fieldui.append(fieldTitle + '<br/><input type="checkbox" name="' + field + '-title"><label for="' + field + '-title">Display title?</label>');
          if (existing[field].hasOwnProperty('title') && parseInt(existing[field]['title'])) {
            $displayTitle.prop('checked', true);
          }
          if (existing[field].hasOwnProperty('prefix')) {
            $prefix.val(existing[field]['prefix']);
          }
          if (existing[field].hasOwnProperty('suffix')) {
            $suffix.val(existing[field]['suffix']);
          }
          if (existing[field].hasOwnProperty('wrapper') && parseInt(existing[field]['wrapper'])) {
            $displayWrapper.prop('checked', true);
          }
        }
      }
    }
  })
})( jQuery );
