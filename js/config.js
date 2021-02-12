/* DYMO Label EM - Project Configuration */
// @ts-check

var DYMOLabelEMConfig = {}

DYMOLabelEMConfig.fileChanged = function (e) {
    var el = document.getElementById('dlem-file')
    var name = 'Choose file ...'
    if (el.files.length > 0) {
        /** @type File */
        var file = el.files[0]
        name = file.name
        var reader = new FileReader();
		reader.addEventListener('load', function(e) {
	    		var text = e.target.result;
                alert(text)
		});
		reader.readAsText(file);
    }
    var nextSibling = e.target.nextElementSibling
    nextSibling.innerText = name
}

DYMOLabelEMConfig.init = function() {
    document.getElementById('dlem-file').addEventListener('change', function(e) {
        DYMOLabelEMConfig.fileChanged(e)
    })
}