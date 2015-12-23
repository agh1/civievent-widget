(function( $ ) {
  $(document).on('ready widget-updated widget-added', function() {
    $('label.civievent-widget-admin-type-label').click( function() {
      $('#' + $(this).attr('for')).click();
    });
    $('input[name^="widget-civievent-widget["][name$="][admin_type]"]:checked').each( function() {
      adminTypeSwitch($(this));
    })
    $('input[name^="widget-civievent-widget["][name$="][admin_type]"]').change( function() {
      adminTypeSwitch($(this));
    });
    function adminTypeSwitch($field) {
      $field.siblings('label[for!="' + $field.attr('id') + '"]').removeClass('selectedTab');
      $field.siblings('label[for="' + $field.attr('id') + '"]').addClass('selectedTab');
      if ($field.val() == 'custom') {
        $field.siblings('.civievent-widget-admin-custom').addClass('selectedTab');
        $field.siblings('.civievent-widget-admin-simple').removeClass('selectedTab');
      }
      else {
        $field.siblings('.civievent-widget-admin-simple').addClass('selectedTab');
        $field.siblings('.civievent-widget-admin-custom').removeClass('selectedTab');
      }
    }
    $('.civievent-widget-getfields').change( function() {
      if ($(this).val() == '') {
        return true;
      }
      $fill = $(this).next('input');
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
      $params = $(this);
      fillCustomDisplay($params);

      $params.next('.show-json').click({ item: $params }, function(event) {
        event.data.item.toggle();
        if ($(this).html() == 'Show JSON') {
          $(this).html('Hide JSON');
        }
        else {
          $(this).html('Show JSON');
        }
      });
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
      fillCustomDisplay($item, field);
    }
    function fillCustomDisplay($item, activeUiField) {
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
            fieldname: field,
          }).appendTo($ui);
          if (field == activeUiField) {
            $fieldui.addClass('active-ui-field');
          }
          var $fieldtitle = $('<div/>', {
            class: 'fieldtitle',
            text: $select.find('option[value="' + field + '"]').html(),
          }).click({ field: field, fieldui: $fieldui }, function(event) {
            var unsetClass = $(event.data.fieldui).hasClass('active-ui-field');
            $('.field-custom-ui.active-ui-field').removeClass('active-ui-field');
            if (!unsetClass) {
              $(event.data.fieldui).addClass('active-ui-field')
            }
          });
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
            $fieldtitle,
            $('<div/>').append(
              $displayTitle,
              $('<label/>', {
                for: field + '-title',
                text: 'Display title?',
              })
            ),
            $('<div/>').append(
              $('<label/>', {
                for: field + '-prefix',
                text: 'Prefix:',
              }),
              ' ',
              $prefix
            ),
            $('<div/>').append(
              $('<label/>', {
                for: field + '-suffix',
                text: 'Suffix:',
              }),
              ' ',
              $suffix
            ),
            $('<div/>').append(
              $displayWrapper,
              $('<label/>', {
                for: field + '-wrapper',
                text: 'Wrap field/title in <span>',
              })
            ),
            $('<div/>').append($remove)
          );
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
