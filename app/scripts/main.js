var everywhereModalTemplate;
$.postJSON = function(url, data, success) {
    return $.post(url, data, success, 'json');
};
$.deleteJSON = function(url, success) {
    return $.ajax({
        type: "DELETE",
        url: url,
        success: success,
        dataType: 'json'
    });
};
$.putJSON = function(url, data, success) {
    return $.ajax({
        type: "PUT",
        url: url,
        data: data,
        success: success,
        dataType: 'json'
    });
};

$.helpers = {
    constants: { 
        emailRegex: /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
    }
};

(function ($) {
    $.fn.enterListener = function(callback) {
        return $(this).on('keyup', function(e) {
            if (e.keyCode === 13) {
                callback && callback(e);
            }
        });
    }
})(jQuery);

$(function() {
    everywhereModalTemplate = $('#EverywhereModalTemplate').remove().html();

    $.getJSON("/api/account/", function(accountInfo) {
        if (accountInfo && accountInfo.IsLoggedIn === true) {
            $(".account-area").html('<span>Welcome back </span><a class="text-console-secondary" href="/account/">' + accountInfo.Username + '</a>');
        } else {
            $(".account-area").html('<span>Welcome. </span><a class="text-console-secondary" href="/account/login">Log in</a>');
        }
    });
});

$.toast = {
    __showToast: function(text, type, duration, period) {
        duration = duration || 350;
        var $toast = $('<div class="dg-toast bg-' + type + '" style="-webkit-transition: all ' + duration + 'ms ease-out; transition: all ' + duration + 'ms ease-out;">' + text + '</div>');
        $('body').append($toast);
        $toast.css('width');
        $toast.addClass('dg-display');
        var close = function() {
            $toast.removeClass('dg-display');
            setTimeout(function() { $toast.remove(); }, duration);
        };
        $toast.data('timeout', setTimeout(function() { close(); }, period || 3000));
        $toast.on('click', function() { clearTimeout($toast.data('timeout')); close(); });
        return $toast;
    },
    success: function(text, duration, period) {
        return this.__showToast(text, 'success', duration, period);
    },
    danger: function(text, duration, period) {
        return this.__showToast(text, 'danger', duration, period);
    },
    warning: function(text, duration, period) {
        return this.__showToast(text, 'warning', duration, period);
    },
    info: function(text, duration, period) {
        return this.__showToast(text, 'info', duration, period);
    }
};

$.everywhere = {
    baseOptions: {
        title: "",
        body: "",
        shownCallback: function(modal, e) {},
        showCallback: function(modal, e) {},
        hiddenCallback: function(modal, e) {},
        enableBackdropClick: true,
        enableEscapeKey: true,
        size: '',
        showImmediately: true
    },
    confirmOptions: {
        title: "Confirm?",
        body: "Are you sure?",
        confirmText: "Confirm",
        confirmIcon: "fas fa-check",
        cancelText: "Cancel",
        cancelIcon: "fas fa-times",
        dismissOnConfirm: true,
        confirmCallback: function(modal, e) {},
        cancelCallback: function(modal, e) {},
        confirmBSColor: 'info',
        confirmClass: '',
        cancelBSColor: 'secondary',
        cancelClass: ''
    },
    confirm: function(options) {
        var settings = $.extend($.extend($.extend({}, this.baseOptions), this.confirmOptions), options);
        var $modal = $(everywhereModalTemplate);
        if (settings.size) {
            $modal.find('.modal-dialog').addClass('modal-' + settings.size);
        }

        $modal.find('.modal-header .modal-title').text(settings.title);
        $modal.find('.modal-body').html(settings.body);

        var $confirmButton = $('<button type="button" class="everywhere-confirm btn w-50 m-0 rounded-0">Confirm</button>');
        if (settings.dismissOnConfirm === true) {
            $confirmButton.attr('data-dismiss', 'modal');
        }
        $confirmButton.html('<i class="' + settings.confirmIcon + '"></i> ' + settings.confirmText).addClass('btn-' + settings.confirmBSColor + ' ' + settings.confirmClass);
        $confirmButton.on('click.everywhere.modal.confirm', function(e) { settings.confirmCallback($modal, e); });
        var $cancelButton = $('<button type="button" class="everywhere-cancel btn w-50 m-0 rounded-0" data-dismiss="modal">Close</button>');
        $cancelButton.html('<i class="' + settings.cancelIcon + '"></i> ' + settings.cancelText).addClass('btn-' + settings.cancelBSColor + ' ' + settings.cancelClass);
        $cancelButton.on('click.everywhere.modal.cancel', function(e) { settings.cancelCallback($modal, e); });
        
        $modal.find('.modal-footer').append($cancelButton);
        $modal.find('.modal-footer').append($confirmButton);
        $modal.on('show.bs.modal', function(e) { settings.showCallback($modal, e); });
        $modal.on('shown.bs.modal', function(e) { settings.shownCallback($modal, e); });
        $modal.on('hidden.bs.modal', function(e) { settings.hiddenCallback($modal, e); $modal.remove().modal('dispose'); });

        $modal.confirmButton = $confirmButton;
        $modal.cancelButton = $cancelButton;
        $modal.disable = function() {
            $modal.find('.everywhere-confirm, .everywhere-cancel, button.close').prop('disabled', true);
        };
        $modal.enable = function() {
            $modal.find('.everywhere-confirm, .everywhere-cancel, button.close').prop('disabled', false);
        };

        return $modal.modal({
            keyboard: settings.enableEscapeKey,
            backdrop: settings.enableBackdropClick || 'static',
            show: settings.showImmediately
        });
    },
    alertOptions: {
        title: "",
        body: "",
        enableBackdropClick: false,
        enableEscapeKey: false,
        size: 'sm'
    },
    alert: function(options) {
        if (typeof options === 'string') {
            options = { body: options };
        }
        var settings = $.extend($.extend($.extend({}, this.baseOptions), this.alertOptions), options);
        var $modal = $(everywhereModalTemplate);
        if (settings.size) {
            $modal.find('.modal-dialog').addClass('modal-' + settings.size);
        }

        if (settings.title) {
            $modal.find('.modal-header .modal-title').text(settings.title);
        } else {
            $modal.find('.modal-header').remove();
        }
        $modal.find('.modal-body').html(settings.body);
        var $button = $('<button type="button" class="everywhere-cancel btn btn-secondary w-100 m-0 rounded-0" data-dismiss="modal">Close</button>');
        $modal.find('.modal-footer').append($button);
        
        $modal.on('show.bs.modal', function(e) { settings.showCallback($modal, e); });
        $modal.on('shown.bs.modal', function(e) { settings.shownCallback($modal, e); });
        $modal.on('hidden.bs.modal', function(e) { settings.hiddenCallback($modal, e); $modal.remove().modal('dispose'); });

        return $modal.modal({
            keyboard: settings.enableEscapeKey,
            backdrop: settings.enableBackdropClick || 'static',
            show: settings.showImmediately
        });
    }
}