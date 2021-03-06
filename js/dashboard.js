
function openImageSelector(id) {
	$(id).click();
}

$.fn.miller = function(args) {
	if(!args.attribute)	
		args.attribute = "value";
	
	$(args.inputId).val(
		$(this).children().css({"display":"block", "marginLeft":"0"}).children().first().addClass("active").attr(args.attribute)
	);
	
	$(this).find("li ul").parent().addClass("parent");

	$(this).click(function(e) {
		if(args.value) {
			if($(e.target).attr(args.attribute))
				$(args.inputId).val($(e.target).attr(args.attribute));
		}
		
		if(!$(e.target).is($(this).first())) {
			$(e.target).addClass("active").children().fadeIn(500).find(".active").removeClass("active");
			$(e.target).siblings().removeClass("active").find("ul").fadeOut(200);
		}
	});
};





function uploadImage(user, categoryName, idForm, idState, idPhoto) {
	var formData = new FormData($(idForm)[0]);
	$.ajax({
		url: 'upload.php?user=' + user + '&category-name=' + categoryName,
		type: 'POST',
		xhr: function() {
			var myXhr = $.ajaxSettings.xhr();
			if(myXhr.upload){
				myXhr.upload.addEventListener('progress',function(e) {
					if(e.lengthComputable) {
					
					}	
				}, false);
			}
			return myXhr;
		},
		beforeSend: function() {
			$(idState).html("<br>Bild wird<br/>hochgeladen ...");
		},
		success: function(data) {
			error1 = parseInt(data[data.length-1]);
			error0 = "";
			
			if(data.indexOf("Length") != -1) {
				error0 = error1;
				error1 = 3;
			}
			
			change_bg_img(idPhoto, "none");
			
			switch(error1) {
				case 1:
					$(idState).html(
						'<span class="icon-cancel-circled"></span><br />' +
						'Fehler beim Hochladen:<br />' +
						'Fehler bei Identifizierung<br />' +
						'<em>Fehlercode 0x' + error1 + error0 + '</em>'
					);
					break;
				case 2:
					$(idState).html(
						'<span class="icon-cancel-circled"></span><br />' +
						'Fehler beim Hochladen:<br />' +
						'Die Datei wurde nicht korrekt übertragen<br />' +
						'<em>Fehlercode 0x' + error1 + error0 + '</em>'
					);
					break;
				case 3:
					$(idState).html(
						'<span class="icon-cancel-circled"></span><br />' +
						'Fehler beim Hochladen:<br />' +
						'Die Datei ist zu groß<br />' +
						'<em>Fehlercode 0x' + error1 + error0 + '</em>'
					);
					break;
				case 4:
					$(idState).html(
						'<span class="icon-cancel-circled"></span><br />' +
						'Fehler beim Hochladen:<br />' +
						'Ungültiges Dateiformat<br />' +
						'Erlaubte Formate: .jpg, .png<br />' +
						'<em>Fehlercode 0x' + error1 + error0 + '</em>'
					);
					break;
				case 5:
					$(idState).html(
						'<span class="icon-cancel-circled"></span><br />' +
						'Datenbankfehler:<br />' +
						'Fehler beim Erstellen der Kategorie<br />' +
						'<em>Fehlercode 0x' + error1 + error0 + '</em>'
					);
					break;
				case 6:
					$(idState).html(
						'<span class="icon-cancel-circled"></span><br />' +
						'Datenbankfehler:<br />' +
						'Unbekannte Kategorie<br />' +
						'<em>Fehlercode 0x' + error1 + error0 + '</em>'
					);
					break;
				case 7:
					$("span#photo-upload-state").html(
						'<span class="icon-cancel-circled"></span><br />' +
						'Datenbankfehler:<br />' +
						'Datei konnte nicht hinzugefügt werden<br />' +
						'<em>Fehlercode 0x' + error1 + error0 + '</em>'
					);
					break;
				case 8:
					$(idState).html(
						'<span class="icon-cancel-circled"></span><br />' +
						'Fehler beim Hochladen:<br />' +
						'Datei konnte nicht hochgeladen werden<br />' +
						'<em>Fehlercode 0x' + error1 + error0 + '</em>'
					);
					break;
				default:
					$(idState).html('<span class="icon-ok-circled"></span><br />Hochladen erfolgreich');
					change_bg_img(idPhoto, data);
					$(idState).parent().addClass("alternate");
			}
		},
		error: function(a,b) {
			alert(b);
		},
		data: formData,
		cache: false,
		contentType: false,
		processData: false
	});
}

function change_bg_img(id, url) {
	$(id).css("background-image", "url('" + url + "')");
}
