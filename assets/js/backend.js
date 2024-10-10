'use strict';

(function($) {
  $(function() {
    // order notes quick view
    $(document).on('click touch', '.wooon-quickview', function(e) {
      var order = $(this).attr('data-order');
      var current = $(this).attr('data-current');

      var data = {
        action: 'wooon_quickview',
        nonce: wooon_vars.nonce,
        order: order,
        current: current,
      };

      $('#wooon_dialog').html('Loading...');
      $('#wooon_dialog').
          dialog({
            minWidth: 460,
            title: 'Order #' + order,
            modal: true,
            dialogClass: 'wpc-dialog',
            open: function() {
              $('.ui-widget-overlay').bind('click', function() {
                $('#wooon_dialog').dialog('close');
              });
            },
          });

      $.post(ajaxurl, data, function(response) {
        $('#wooon_dialog').html(response);

        $('#wooon_dialog').find('li.note:not(.system-note)').each(function() {
          $(this).
              find('.meta').
              append('<a href="#" class="edit_note">Edit note</a>');
        });
      });

      e.preventDefault();
    });

    $(document).on('click touch', '#wooon_dialog a.delete_note', function() {
      if (window.confirm(woocommerce_admin_meta_boxes.i18n_delete_note)) {
        var note = $(this).closest('li.note');

        $(note).block({
          message: null,
          overlayCSS: {
            background: '#fff',
            opacity: 0.6,
          },
        });

        var data = {
          action: 'woocommerce_delete_order_note',
          note_id: $(note).attr('rel'),
          security: woocommerce_admin_meta_boxes.delete_order_note_nonce,
        };

        $.post(woocommerce_admin_meta_boxes.ajax_url, data, function() {
          $(note).remove();
        });
      }

      return false;
    });

    $(document).on('click touch', '#wooon_dialog button.add_note', function() {
      if (!$('textarea#add_order_note').val()) {
        return;
      }

      $('#woocommerce-order-notes').block({
        message: null,
        overlayCSS: {
          background: '#fff',
          opacity: 0.6,
        },
      });

      var data = {
        action: 'woocommerce_add_order_note',
        post_id: $('#woocommerce-order-notes').data('id'),
        note: $('textarea#add_order_note').val(),
        note_type: $('select#order_note_type').val(),
        security: woocommerce_admin_meta_boxes.add_order_note_nonce,
      };

      $.post(woocommerce_admin_meta_boxes.ajax_url, data, function(response) {
        $('ul.order_notes .no-items').remove();
        $('ul.order_notes').prepend(response);
        $('#woocommerce-order-notes').unblock();
        $('#add_order_note').val('');
        window.wcTracks.recordEvent('order_edit_add_order_note', {
          order_id: data.post_id,
          note_type: data.note_type || 'private',
          status: $('#order_status').val(),
        });

        // edit button
        $('#wooon_dialog').find('li.note:not(.system-note)').each(function() {
          if (!$(this).find('.edit_note').length) {
            $(this).
                find('.meta').
                append('<a href="#" class="edit_note">Edit note</a>');
          }
        });
      });

      return false;
    });

    $(document).
        on('click touch', '#wooon_dialog button.bulk_add_note', function() {
          if (!$('textarea#add_order_note').val()) {
            return;
          }

          $('#woocommerce-order-notes').block({
            message: null,
            overlayCSS: {
              background: '#fff',
              opacity: 0.6,
            },
          });

          var data = {
            action: 'wooon_add_order_note',
            ids: $('div.bulk_add_note').data('ids'),
            note: $('textarea#add_order_note').val(),
            note_type: $('select#order_note_type').val(),
          };

          $.post(woocommerce_admin_meta_boxes.ajax_url, data,
              function(response) {
                $('#woocommerce-order-notes').unblock();
                window.location.href = $('div.bulk_add_note').data('redirect');
              });

          return false;
        });

    $(document).on('click touch', '#wooon_dialog a.edit_note', function() {
      var $this = $(this).closest('li.note');
      var old_note = $this.find('.note_content').text().trim();

      $this.find('.note_content').html(
          '<textarea type="text" class="input-text" cols="20" rows="5">' +
          old_note +
          '</textarea><button type="button" class="update_order_note">Update</button>');
    });

    $(document).
        on('click touch', '#wooon_dialog button.update_order_note', function() {
          $('#woocommerce-order-notes').block({
            message: null,
            overlayCSS: {
              background: '#fff',
              opacity: 0.6,
            },
          });

          var $this = $(this).closest('li.note');

          var data = {
            action: 'wooon_update_order_note',
            note_id: $this.attr('rel'),
            note_content: $this.find('textarea').val(),
          };

          $.post(woocommerce_admin_meta_boxes.ajax_url, data,
              function(response) {
                $this.find('.note_content').html('<p>' + response + '</p>');
                $('#woocommerce-order-notes').unblock();
              });
        });
  });
})(jQuery);