/** This file is part of KCFinder project
  *
  *      @desc Object initializations
  *   @package KCFinder
  *   @version 2.54
  *    @author Pavel Tzonkov <sunhater@sunhater.com>
  * @copyright 2010-2014 KCFinder Project
  *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
  *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
  *      @link http://kcfinder.sunhater.com
  */
browser.init = function() {
    if (!this.checkAgent()) return;

    $('body').click(function() {
        browser.hideDialog();
    });
    $('#shadow').click(function() {
        return false;
    });
    $('#dialog').unbind();
    $('#dialog').click(function() {
        return false;
    });
    $('#alert').unbind();
    $('#alert').click(function() {
        return false;
    });
    this.initOpeners();
    this.initSettings();
    this.initContent();
    this.initToolbar();
    this.initUploader();
    this.initResizer();
};

browser.checkAgent = function() {
    return true;
};

browser.initOpeners = function() {
    if (this.opener.TinyMCE && (typeof(tinyMCEPopup) == 'undefined'))
        this.opener.TinyMCE = null;

    if (this.opener.TinyMCE)
        this.opener.callBack = true;

    if ((!this.opener.name || (this.opener.name == 'fckeditor')) &&
        window.opener && window.opener.SetUrl
    ) {
        this.opener.FCKeditor = true;
        this.opener.callBack = true;
    }

    if (this.opener.CKEditor) {
        if (window.parent && window.parent.CKEDITOR)
            this.opener.CKEditor.object = window.parent.CKEDITOR;
        else if (window.opener && window.opener.CKEDITOR) {
            this.opener.CKEditor.object = window.opener.CKEDITOR;
            this.opener.callBack = true;
        } else
            this.opener.CKEditor = null;
    }

    if (this.opener.name && (this.opener.name == "tinymce4"))
            this.opener.callBack = true;

    if (!this.opener.CKEditor && !this.opener.FCKEditor && !this.TinyMCE) {
        if ((window.opener && window.opener.KCFinder && window.opener.KCFinder.callBack) ||
            (window.parent && window.parent.KCFinder && window.parent.KCFinder.callBack)
        )
            this.opener.callBack = window.opener
                ? window.opener.KCFinder.callBack
                : window.parent.KCFinder.callBack;

        if ((
                window.opener &&
                window.opener.KCFinder &&
                window.opener.KCFinder.callBackMultiple
            ) || (
                window.parent &&
                window.parent.KCFinder &&
                window.parent.KCFinder.callBackMultiple
            )
        )
            this.opener.callBackMultiple = window.opener
                ? window.opener.KCFinder.callBackMultiple
                : window.parent.KCFinder.callBackMultiple;
    }
};

browser.initContent = function() {
    $('div#folders').html(this.label("Loading folders..."));
    $('div#files').html(this.label("Loading files..."));
    $.ajax({
        type: 'GET',
        dataType: 'json',
        url: browser.baseGetData('init'),
        async: false,
        success: function(data) {
            if (browser.check4errors(data))
                return;
            browser.dirWritable = data.dirWritable;
            $('#folders').html(browser.buildTree(data.tree));
            browser.setTreeData(data.tree);
            browser.initFolders();
            browser.files = data.files ? data.files : [];
            browser.orderFiles();
        },
        error: function() {
            $('div#folders').html(browser.label("Unknown error."));
            $('div#files').html(browser.label("Unknown error."));
        }
    });
};

browser.initResizer = function() {
    var cursor = 'col-resize';
    $('#resizer').css('cursor', cursor);
    $('#resizer').drag('start', function() {
        $(this).css({opacity:'0.4', filter:'alpha(opacity:40)'});
        $('#all').css('cursor', cursor);
    });
    $('#resizer').drag(function(e) {
        var left = e.pageX - parseInt(_.nopx($(this).css('width')) / 2);
        left = (left >= 0) ? left : 0;
        left = (left + _.nopx($(this).css('width')) < $(window).width())
            ? left : $(window).width() - _.nopx($(this).css('width'));
		$(this).css('left', left);
	});
	var end = function() {
        $(this).css({opacity:'0', filter:'alpha(opacity:0)'});
        $('#all').css('cursor', '');
        var left = _.nopx($(this).css('left')) + _.nopx($(this).css('width'));
        var right = $(window).width() - left;
        $('#left').css('width', left + 'px');
        $('#right').css('width', right + 'px');
        _('files').style.width = $('#right').innerWidth() - _.outerHSpace('#files') + 'px';
        _('resizer').style.left = $('#left').outerWidth() - _.outerRightSpace('#folders', 'm') + 'px';
        _('resizer').style.width = _.outerRightSpace('#folders', 'm') + _.outerLeftSpace('#files', 'm') + 'px';
        browser.fixFilesHeight();
    };
    $('#resizer').drag('end', end);
    $('#resizer').mouseup(end);
};

browser.resize = function() {
    _('left').style.width = '25%';
    _('right').style.width = '75%';
    _('toolbar').style.height = $('#toolbar a').outerHeight() + "px";
    _('shadow').style.width = $(window).width() + 'px';
    _('shadow').style.height = _('resizer').style.height = $(window).height() + 'px';
    _('left').style.height = _('right').style.height =
        $(window).height() - $('#status').outerHeight() + 'px';
    _('folders').style.height =
        $('#left').outerHeight() - _.outerVSpace('#folders') + 'px';
    browser.fixFilesHeight();
    var width = $('#left').outerWidth() + $('#right').outerWidth();
    _('status').style.width = width + 'px';
    while ($('#status').outerWidth() > width)
        _('status').style.width = _.nopx(_('status').style.width) - 1 + 'px';
    while ($('#status').outerWidth() < width)
        _('status').style.width = _.nopx(_('status').style.width) + 1 + 'px';
    _('files').style.width = $('#right').innerWidth() - _.outerHSpace('#files') + 'px';
    _('resizer').style.left = $('#left').outerWidth() - _.outerRightSpace('#folders', 'm') + 'px';
    _('resizer').style.width = _.outerRightSpace('#folders', 'm') + _.outerLeftSpace('#files', 'm') + 'px';
};

browser.fixFilesHeight = function() {
    _('files').style.height =
        $('#left').outerHeight() - $('#toolbar').outerHeight() - _.outerVSpace('#files') -
        (($('#settings').css('display') != "none") ? $('#settings').outerHeight() : 0) + 'px';
};
