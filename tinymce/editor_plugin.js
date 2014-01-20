/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */

(function() {
	tinymce.create('tinymce.plugins.poofPlugin', {
		init : function(ed, url) {
			this.editor = ed;
			
			//get itemid cmid and courseid if available
			var itemid = this.getsimpleitemid();
			if(!itemid){
				itemid=this.getcomplexitemid();
        	}
        	if(itemid){
        		itemid = itemid.value;
        	}else{
        		itemid =0;
        	}
			var cmid= this.getreqparam('cmid');
			if(!cmid){
				cmid= this.getreqparam('update');
				if(!cmid){
					cmid= this.getreqparam('id');
				}
			}
			if(!cmid){cmid=0;}
			var courseid = this.getreqparam('courseid');
			if (!courseid){courseid=0;}	

			// Register commands
				ed.addCommand('mcepoof', 
					function() {
						return function(){
							var areahtml = ed.selection.getContent({format : 'raw'});//ed.getContent({format : 'raw'});
							areahtml =encodeURIComponent(areahtml);
							if(areahtml && areahtml.length > 8192){
								alert("Selected content may be too long. Maximum is between. 2kb and 8kb characters. Good luck ..");
							}
							ed.windowManager.open({
								file : ed.getParam("moodle_plugin_base") + 'poof/tinymce/poof.php?itemid='+itemid + '&areahtml=' + areahtml + '&courseid=' + courseid + '&cmid=' + cmid,
								width : 350 + parseInt(ed.getLang('advlink.delta_width', 0)),
								height : 400 + parseInt(ed.getLang('advlink.delta_height', 0)),
								inline : 1
							}, {
								plugin_url : url
							});
							
						}
					}()
				);

			// Register buttons
			ed.addButton('poof', {
				title : 'poof.desc',
				cmd : 'mcepoof',
				image: url + '/img/icon.gif'
			});
			
			 // Add a node change handler, selects the button in the UI when text selected
            ed.onNodeChange.add(function(ed, cm, n) {
                var p, c;
                c = cm.get('poof');
                if (!c) {
                    // Button not used.
                    return;
                }
				
                if (ed.selection.getContent() && (document.getElementById("mce_fullscreen_container")===null)) {
                    c.setDisabled(false);
                } else {
                    c.setDisabled(true);
                }
            });
			
		},
		
		getsimpleitemid : function(){
        var formtextareaid = tinyMCE.activeEditor.id;
        var formtextareaname = formtextareaid.substr(0,formtextareaid.length-3);
        var itemidname =  formtextareaname + ':itemid';
        var itemid = window.top.document.getElementsByName(itemidname).item(0);
        return itemid;
	},
	
	getreqparam: function (paramname){
		   if(paramname=(new RegExp('[?&]'+encodeURIComponent(paramname)+'=([^&]*)')).exec(location.search))
			  return decodeURIComponent(paramname[1]);
		},
	
	getcomplexitemid : function(){
			var formtextareaid = tinyMCE.activeEditor.id.substr(3);
			var formtextareatmp = formtextareaid.split("_");
			if (formtextareatmp.length == 2 && !isNaN(formtextareatmp[1])) {
			   var itemidname = formtextareatmp[0] + '[' + formtextareatmp[1] + '][itemid]';
			}
			else {
			   var itemidname = formtextareaid + '[itemid]';   
			}
			var itemid = window.top.document.getElementsByName(itemidname).item(0);
			return itemid;
	},

		getInfo : function() {
			return {
				longname : 'Legacy Files Go Poof',
				author : 'Justin Hunt',
				version : '1.0.0'
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('poof', tinymce.plugins.poofPlugin);
})();