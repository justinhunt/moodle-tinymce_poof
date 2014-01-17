/* Functions for the Legacy Files Go Poof plugin popup */

tinyMCEPopup.requireLangPack();


var tinymce_poof_Dialog = {
	init : function() {
		this.resize();
	},

	insert : function(newcontent) {
		var nc = decodeURIComponent(newcontent,{format : 'raw'});
		tinyMCEPopup.editor.selection.setContent(nc);
		tinyMCEPopup.close();
	},

	resize : function() {
		var vp = tinyMCEPopup.dom.getViewPort(window), el;

		el = document.getElementById('content');

		el.style.width  = (vp.w - 20) + 'px';
		el.style.height = (vp.h - 90) + 'px';
	}
};

tinyMCEPopup.onInit.add(tinymce_poof_Dialog.init, tinymce_poof_Dialog);