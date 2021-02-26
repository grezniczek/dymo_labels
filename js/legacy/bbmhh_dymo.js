/**
 * Created by GR on 17.11.2016.
 */

var dymoPrinterName = null;
var calData = [];

function setupPrinters(container_id, calibrationUrl) {
    var printers = dymo.label.framework.getPrinters();
    // Get calibration data
    $.get(calibrationUrl, function(calDataJSON) {
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


function genDM(code, rot) {

    if (!code) return "";

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
        BWIPP()(bw, "datamatrix", code, opts);
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

    var canvas = document.createElement("canvas");
    bw.bitmap().show(canvas, rot);
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

function previewDatamatrix(id) {
    var code = $('#code').val();
    var rot = "N"; // one of N, R, L, I (Normal, Right, Left, Inverted)

    var png = genDM(code, rot);
    $("#"+id).attr("src", "data:image/png;base64, " + png);
}

function printLabel(labelUrl, code, text, date, project) {

    $('#btnPrint').prop('disabled', true).css("cursor", "not-allowed").css("font-size", "80%").text("GEDRUCKT");

    $.get(labelUrl, function(labelXml) {

        // cap mode (text or datamatrix)
        var cap_text = $('#cap_text').prop("checked") == true;

        // load label definition
        var doc = $.parseXML(labelXml);
        // get calibration data for printer (if any)
        var cal = getPrinterCalibration(dymoPrinterName, calData);
        // conversion of 1/10mm to twips = 1440/254
        var dx = 1440 * cal.dx / 254
        var dy = 1440 * cal.dy / 254;

        // adjust bounds
        $(doc).find('Bounds').each(function(i, el) {
            el = $(el);
            var x = Number(el.attr("X"));
            var y = Number(el.attr("Y"));
            x = x + dx;
            y = y + dy;
            // set new
            el.attr("X", x).attr("Y", y);
        });

        // convert back to xml string
        labelXml = (new XMLSerializer()).serializeToString(doc);

        if (dymoPrinterName == null)  {
            alert("Kein Drucker ausgewählt");
        }
        else {
            try {
                var printParams = {};
                printParams.copies = Math.min(Math.max($('#n').val(), 1), 20);
                printParams.jobTitle = "BBMHH Label - " + code;
                printParams.printQuality = dymo.label.framework.LabelWriterPrintQuality.BarcodeAndGraphics;
                var printParamsXml = dymo.label.framework.createLabelWriterPrintParamsXml(printParams);

                var label = dymo.label.framework.openLabelXml(labelXml);
                label.setObjectText("CODE", code);
                label.setObjectText("TEXT", text);
                label.setObjectText("DATE", date);
                var dm = genDM(code, "N");
                label.setObjectText("DMLABEL", dm);
                if (cap_text) {
                    var middle = code.length / 2;
                    var line1 = code.substring(0, middle);
                    var line2 = code.substring(middle);
                    label.setObjectText("CODECAP", line1 + "\n" + line2);
                    var empty = emptyPng();
                    label.setObjectText("DMCAP", empty);
                }
                else {
                    label.setObjectText("DMCAP", dm);
                    label.setObjectText("CODECAP", "");
                }
                label.setObjectText("PROJECT", project);
                label.print(dymoPrinterName, printParamsXml);
            }
            catch (e) {
                $('#error').append("<p>" + e + "</p>");
                $('#btnPrint').prop('disabled', false).css("cursor", "pointer").css("font-size", "100%").text("DRUCKEN");
            }
        }
    });

}

function toggleCalibrate(calibrationUrl) {
    var cal = $('#calibrate');
    if (cal.hasClass("hidden")) {
        cal.removeClass("hidden");
    }
    else {
        cal.addClass("hidden");
    }
}