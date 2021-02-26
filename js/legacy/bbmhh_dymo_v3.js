/**
 * Created by GR on 17.11.2016.
 * 
 * 
 * https://stackoverflow.com/questions/11403333/httplistener-with-https-support
 * 
 */

var dymoPrinterName = null;
var calData = [];
var calDataUrl = '/dymo/calibration/cal_data.json';
var calSaveUrl = '/dymo/calibrate.php';
var printData = null;

function setPrintData(data) {
    printData = data;
    preprocessData(printData);
}

function setupPrinters(container_id) {
    var printers = dymo.label.framework.getPrinters();
    // Get calibration data
    $.get(calDataUrl, function(calDataJSON) {
        // Store calibration data
        calData = calDataJSON;
        // Display list of printers
        for (var i = 0; i < printers.length; i++) {
            $('#'+container_id).append("<p><input onchange='setPrinter(\"pr"+i+"\")' type='radio' id='pr" + i + "' value='" + printers[i].name + "' /> <label for='pr" + i + "'>" + printers[i].name + "</label></p>");
        }
        if (printers.length > 0) {
            setPrinter('pr0');
        }
    });
}

function setPrinter(id) {
    var printer = $('#'+id);
    printer.prop('checked', true);
    dymoPrinterName = printer.val();

    // set the calibration data in the input fields
    var cal = getPrinterCalibration(dymoPrinterName, calData);
    $('#cal_dx').val(cal.dx);
    $('#cal_dy').val(cal.dy);
    $('#cal_name').val(cal.name);

    if (printData.auto == true) {
        printLabels();
    }
}

function getPrinterCalibration(name, data) {
    var cal = { 'name': name, 'dx': 0, 'dy': 0 };
    for (var i = 0; i < data.length; i++) {
        if (data[i].name == name) {
            cal.dx = data[i].dx;
            cal.dy = data[i].dy;
            return cal;
        }
    }
    return cal;
}

function saveCalibration() {
    var cal = {};
    cal.name = $('#cal_name').val();
    cal.dx = $('#cal_dx').val();
    cal.dy = $('#cal_dy').val();

    $.ajax({
        url: calSaveUrl,
        contentType: 'application/json; charset=UTF-8',
        data: JSON.stringify(cal),
        method: 'POST'
    });

    toggleCalibrate();
}

function generateBarcode(code, type, rot) {

    if (!code) return "";
    if (!type) type = 'datamatrix';
    if (!(type == 'datamatrix' || type == 'qrcode'))
    {
        return "";
    }

    switch (rot) {
        case "N":
        case "L":
        case "R":
        case "I":
            break;
        default:
            rot = "N";
            break;
    }

    var opts = {};

    // Instantiate BWIPJS, unconfigured freetype will do, and we will be monochrome
    var bw = new BWIPJS(Module, true);
    // Create bitmap
    bw.bitmap(new Bitmap());
    // Set the scaling factors
    bw.scale(2, 2);
    // Render the bar code
    try {
        // Create a new BWIPP instance
        BWIPP()(bw, type, code, opts);
    } catch (e) {
        // Watch for BWIPP generated raiseerror's.
        var msg = '' + e;
        if (msg.indexOf("bwipp.") >= 0) {
            alert(msg);
        } else if (e.stack) {
            alert(e.stack);
        } else {
            alert(e);
        }
        return;
    }

    // Put the barcode on a canvas
    var canvas = document.createElement("canvas");
    bw.bitmap().show(canvas, rot);
    // And convert it to a PNG data stream
    var png = canvas.toDataURL();
    png = png.substr(png.indexOf(',')+ 1).trim();

    return png;
}

function emptyPng() {
    var canvas = document.createElement("canvas");
    var png = canvas.toDataURL();
    png = png.substr(png.indexOf(',')+ 1).trim();
    return png;
}

function renderPreview(id) {

    var cols = [];
    var rows = [];

    // Loop through all labels
    for (var labelIdx in printData.labels) {
        rows[labelIdx] = [];
        var label = printData.labels[labelIdx];
        for (var elIdx in label) {
            var el = label[elIdx];
            cols[el.name] = { name: el.name, type: el.type };
            rows[labelIdx][el.name] = el.value;
        }
    }

    var table = '<table class="preview"><tr>';
    for (var c in cols) {
        table += '<th>' + cols[c].name + '</th>';
    }
    table += '</tr>';

    for (var r in rows) {
        table += '<tr>';
        for (var c in cols) {
            var name = cols[c].name;
            var row = rows[r];
            switch(cols[c].type) {
                case 'T':
                    table += '<td><pre>' + row[name] + '</pre></td>';
                    break;
                case 'R':
                    table += '<td><span class="removed">REMOVED</span></td>';
                    break;
                case 'DM':
                case 'QR':
                    table += '<td><img src="data:image/png;base64, ' + row[name] + '" /></td>';
                    break;
                default:
                    table += '<td></td>';
                    break;
            }
        }
        table += '</tr>';
    }
    table += '</table>';


    $('#'+id).append(table);
}

function printLabels() {

    if (dymoPrinterName == null)  {
        alert("Kein Drucker ausgewählt");
        return false;
    }

    // Prevent button from being pressed again
    $('#btnPrint').prop('disabled', true).css("cursor", "not-allowed").css("font-size", "80%").text("GEDRUCKT");

    var templateUrl = '/dymo/templates/'+printData.template+'.label';

    if (printData.templateXml) {
        processPrintQueue(printData.templateXml, printData.labels);
    }
    else {
        $.get(templateUrl, function(templateXml) {
            processPrintQueue(templateXml, printData.labels);
        });
    }
}

function preprocessData(data) {
    // Do nothing if preprocessing already has taken place
    if (data.preprocessed) return;

    for (var i = 0; i < data.labels.length; i++) {
        var label = data.labels[i];
        for (var j = 0; j < label.length; j++) {
            var el = label[j];
            switch(el.type) {
                case 'QR':
                    el.value = generateBarcode(el.value, 'qrcode');
                    break;
                case 'DM':
                    el.value = generateBarcode(el.value, 'datamatrix');
                    break;
                default:
                    break;
            }
        }
    }
    data.preprocessed = true;
}

function getLabelElementValue(elementName, label) {
    for (element in label) {
        if (element.name == elementName) {
            return element.value;
        }
    }
    return false;
}

function processPrintQueue(xml, labels) {

    // Get calibration data for printer (if any)
    var cal = getPrinterCalibration(dymoPrinterName, calData);
    // Conversion of 1/10mm to twips = 1440/254
    var dx = 1440 * cal.dx / 254
    var dy = 1440 * cal.dy / 254;

    // Loop through all labels
    for (var labelIdx in labels) {
        var label = labels[labelIdx];
        // Load label definition
        var doc = $.parseXML(xml);

        // Adjust bounds
        $(doc).find('Bounds').each(function (i, el) {
            el = $(el);
            var x = Number(el.attr("X"));
            var y = Number(el.attr("Y"));
            x = x + dx;
            y = y + dy;
            // Set new bounds from calibration data
            el.attr("X", x).attr("Y", y);
        });

        // Setup print params
        try {
            var printParams = {};
            printParams.copies = 1;
            printParams.jobTitle = "Label #" + labelIdx;
            printParams.printQuality = dymo.label.framework.LabelWriterPrintQuality.BarcodeAndGraphics;
            var paramsXml = dymo.label.framework.createLabelWriterPrintParamsXml(printParams);
        }
        catch (e) {
            $('#error').append("<p>" + e + "</p>");
            return false;
        }

        // Remove objects from Xml
        for (var elIdx in label) {
            var labelObject = label[elIdx];
            if (labelObject.type == 'R') {
                $(doc).find('Name').each(function (i, el) {

                    if (el.textContent == labelObject.name) {
                        $(el.parentNode.parentNode).remove();
                    }
                });
            }
        }

        // Convert modified DOM back to xml
        var labelXml = (new XMLSerializer()).serializeToString(doc);

        // Print an individual label
        printLabel(label, labelXml, paramsXml);
    }
}

function printLabel(labelData, labelXml, paramsXml) {

    try {
        var label = dymo.label.framework.openLabelXml(labelXml);
        for (var i in labelData) {
            var field = labelData[i];
            if (field.type != 'R') {
                label.setObjectText(field.name, field.value);
            }

        }

        label.print(dymoPrinterName, paramsXml);
    }
    catch (e) {
        $('#error').append("<p>" + e + "</p>");
        $('#btnPrint').prop('disabled', false).css("cursor", "pointer").css("font-size", "100%").text("DRUCKEN");
    }
}

function previewLabel(labelData, labelXml, renderParamsXml) {

    try {
        renderParamsXml = dymo.label.framework.createLabelRenderParamsXml({
            labelColor: {a: 255, r: 200, g: 200, b: 200},
            flowDirection: dymo.label.framework.FlowDirection.LeftToRight,
            shadowDepth: 0,
            pngUseDisplayResolution: false
        });
    }
    catch (e) {
        $('#error').append("<p>" + e + "</p>");
    }

    try {
        var label = dymo.label.framework.openLabelXml(labelXml);
        for (var i in labelData) {
            var field = labelData[i];
            if (field.type != 'R') {
                label.setObjectText(field.name, field.value);
            }

        }

        return label.render(renderParamsXml, dymoPrinterName);
    }
    catch (e) {
        $('#error').append("<p>" + e + "</p>");
        $('#btnPrint').prop('disabled', false).css("cursor", "pointer").css("font-size", "100%").text("DRUCKEN");
    }

    return "";
}

function toggleCalibrate() {
    var cal = $('#calibrate');
    if (!dymoPrinterName) {
        cal.addClass("hidden");
        return;
    }
    if (cal.hasClass("hidden")) {
        cal.removeClass("hidden");
    }
    else {
        cal.addClass("hidden");
    }
}