jQuery(document).ready(function($){

    /* ---------- tabs ---------- */
    var ig_page_url  = new URL(window.location.href);

    function ig_open_tab(name){
        var $btn = $('.wrap.ig_settings .nav-tabs button.nav-link[aria-controls="'+name+'"]');
        if($btn.length){ $btn.trigger('click'); }
    }

    if(ig_page_url.searchParams.get('tab')){
        ig_open_tab(ig_page_url.searchParams.get('tab'));
    }

    $('.wrap.ig_settings .nav-tabs button.nav-link').on('click', function(){
        var this_tab_name = $(this).attr('aria-controls');
        var this_url  = new URL(window.location.href);
        this_url.searchParams.set('tab', this_tab_name);
        window.history.replaceState('', '', this_url.href);
    });

    $('.ig-tab-jump').on('click', function(e){
        e.preventDefault();
        ig_open_tab($(this).data('tab'));
    });

    /* ---------- "how it works" panel ---------- */
    $('.ig_how_link').on('click', function(e){
        e.preventDefault();
        $('.ig_dismiss_link').parent().show();
        $(this).fadeOut();
    });

    $('.ig_dismiss_link').on('click', function(e){
        e.preventDefault();
        $(this).parent().hide();
        $('.ig_how_link').fadeIn();
    });

    /* ---------- helpers ---------- */
    function ig_error(resp){
        var msg = ig_obj.error_generic;
        if(resp && resp.responseJSON && resp.responseJSON.data && resp.responseJSON.data.message){
            msg = resp.responseJSON.data.message;
        }
        alert(msg);
    }

    function ig_update_action(this_actions, action_type){
        var this_type = this_actions.find('a[data-type="'+action_type+'"]');
        var other_type = action_type == 'whitelist' ? 'blacklist' : 'whitelist';
        var other_type_obj = this_actions.find('a[data-type="'+other_type+'"]');

        other_type_obj.removeClass('hide');
        this_type.addClass('hide');

        this_actions.closest('li.ig_param_row').attr('data-blocked', action_type == 'blacklist' ? '1' : '0');
    }

    /* ---------- single whitelist / blacklist ---------- */
    $('body').off('click', '.ig_actions a');
    $('body').on('click', '.ig_actions a', function(e){
        e.preventDefault();
        if(!ig_obj.ig_super_admin){ alert(ig_obj.ig_super_admin_msg); return false; }
        var aClicked = $(this);
        var actions  = aClicked.parent();
        var type     = aClicked.attr('data-type');

        $.blockUI({message:''});
        $.post(ig_obj.ajaxurl, {
            action: 'ig_update',
            type: type,
            val: actions.attr('data-val'),
            uri_index: actions.attr('data-uri'),
            ig_nonce: ig_obj.ig_nonce
        }).done(function(response){
            if(response && response.success){
                ig_update_action(actions, type);
            }else{
                ig_error();
            }
        }).fail(ig_error).always(function(){
            $.unblockUI();
        });
    });

    /* ---------- bulk actions ---------- */
    $('ul.ig_log_list').on('change', 'input:checkbox', function(){
        var all_checked = $('ul.ig_log_list input:checkbox:checked');
        var ig_action_bulk = $('ul.ig_bulk_action .ig_actions_selected');
        ig_action_bulk.css('visibility', all_checked.length > 0 ? 'visible' : 'hidden');
    });

    $('ul.ig_bulk_action .ig_params input:checkbox').on('click', function(){
        var all_checkbox = $('ul.ig_log_list li.ig_param_row:not(.ig-hidden) input:checkbox');
        all_checkbox.prop('checked', $(this).prop('checked'));
        $('ul.ig_log_list input:checkbox').first().trigger('change');
    });

    $('.ig_bulk_action .ig_actions_selected a').on('click', function(e){
        e.preventDefault();

        if(!ig_obj.ig_super_admin){ alert(ig_obj.ig_super_admin_msg); return false; }

        var action_type = $(this).data('type');
        var all_checked = $('ul.ig_log_list input:checkbox:checked');

        if(all_checked.length < 1){ return; }

        var post_obj = {};
        all_checked.each(function(){
            var this_uri = $(this).data('uri');
            var this_val = $(this).val();
            if(!$.isArray(post_obj[this_uri])){
                post_obj[this_uri] = [];
            }
            post_obj[this_uri].push(this_val);
        });

        $.blockUI({message:''});
        $.post(ig_obj.ajaxurl, {
            action: 'ig_update_bulk_backlist',
            ig_post_obj: post_obj,
            ig_type: action_type,
            ig_nonce: ig_obj.ig_nonce
        }).done(function(resp){
            if(resp && resp.success){
                document.location.reload();
            }else{
                $.unblockUI();
                ig_error();
            }
        }).fail(function(r){
            $.unblockUI();
            ig_error(r);
        });
    });

    /* ---------- log search / filter ---------- */
    function ig_apply_filter(){
        var q  = $.trim($('#ig_log_filter').val() || '').toLowerCase();
        var st = $('#ig_log_status').val() || '';
        var shown = 0;

        $('li.ig_uri_row').each(function(){
            var $uri     = $(this);
            var uriMatch = (q === '') || (($uri.attr('data-uri-text') || '').indexOf(q) !== -1);
            var any      = false;

            $uri.find('li.ig_param_row').each(function(){
                var $r = $(this);
                var ok = uriMatch || (($r.attr('data-name') || '').indexOf(q) !== -1);

                if(ok && st === 'blocked'){ ok = ($r.attr('data-blocked') === '1'); }
                if(ok && st === 'allowed'){ ok = ($r.attr('data-blocked') !== '1'); }
                if(ok && st === 'today'){   ok = ($r.attr('data-today') === '1'); }

                $r.toggleClass('ig-hidden', !ok);
                if(ok){ any = true; shown++; }
            });

            $uri.toggleClass('ig-hidden', !any);
        });

        $('#ig_log_count').text(shown ? shown + ' / ' + $('li.ig_param_row').length : '');
    }

    $('#ig_log_filter').on('input', ig_apply_filter);
    $('#ig_log_status').on('change', ig_apply_filter);

    /* ---------- clear lists ---------- */
    $('.ig_clear').on('click', function(){
        if(!ig_obj.ig_super_admin){ alert(ig_obj.ig_super_admin_msg); return false; }
        if(!window.confirm(ig_obj.confirm_clear)){ return; }

        $.blockUI({message:''});
        $.post(ig_obj.ajaxurl, {
            action: 'ig_clear_data',
            what: $(this).data('what'),
            ig_nonce: ig_obj.ig_nonce
        }).done(function(resp){
            if(resp && resp.success){
                document.location.reload();
            }else{
                $.unblockUI();
                ig_error();
            }
        }).fail(function(r){
            $.unblockUI();
            ig_error(r);
        });
    });

    /* ---------- capability audit ---------- */
    var ig_cap_audit_offset  = 0;
    var ig_cap_audit_loaded  = 0;
    var ig_cap_audit_loading = false;
    var ig_cap_audit_per_page = 100;

    function ig_cap_audit_filter(){
        var q       = $.trim($('#ig_audit_filter').val() || '').toLowerCase();
        var flagged = $('#ig_audit_flagged_only').prop('checked');

        $('#cap-audit-results tr').each(function(){
            var $tr = $(this);
            var ok  = (q === '') || ($tr.text().toLowerCase().indexOf(q) !== -1);
            if(ok && flagged){ ok = $tr.hasClass('flag'); }
            $tr.toggleClass('ig-hidden', !ok);
        });
    }

    function load_ig_cap_audit_report() {
        if (ig_cap_audit_loading) return;
        var $results = $('#cap-audit-results');
        if (!$results.length) return;

        ig_cap_audit_loading = true;

        $.get(ig_obj.ajaxurl, {
            action: 'ig_load_capability_audit',
            offset: ig_cap_audit_offset,
            per_page: ig_cap_audit_per_page,
            security: ig_obj.ig_nonce
        }).done(function(resp){
            ig_cap_audit_loading = false;

            if(!resp || !resp.success){
                $('#cap-audit-status').text(ig_obj.error_generic);
                return;
            }

            var count = parseInt(resp.data.count, 10) || 0;

            if(count > 0){
                // rows are escaped server side; wrap them so the HTML parser keeps the <tr> elements
                var $rows = $('<table><tbody>' + resp.data.rows + '</tbody></table>').find('tr');
                $results.append($rows);
                ig_cap_audit_loaded += count;
                ig_cap_audit_offset += ig_cap_audit_per_page;
                $('#cap-audit-status').text(ig_obj.audit_count.replace('%d', ig_cap_audit_loaded));
                ig_cap_audit_filter();
            }

            if(count >= ig_cap_audit_per_page){
                load_ig_cap_audit_report();
            }else{
                $('#cap-audit-status').text(ig_obj.audit_done);
            }
        }).fail(function(){
            ig_cap_audit_loading = false;
            $('#cap-audit-status').text(ig_obj.error_generic);
        });
    }

    $('#ig_audit_filter').on('input', ig_cap_audit_filter);
    $('#ig_audit_flagged_only').on('change', ig_cap_audit_filter);

    load_ig_cap_audit_report();
});
