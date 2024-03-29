<?php namespace DE\RUB\DYMOLabelsExternalModule;

require_once ("classes/Crypto.php");
require_once ("classes/Project.php");

/**
 * This is setup plugin page
 */
class setupPluginPage { 
    
    /**
     * Processes the request.
     * @param DYMOLabelsExternalModule $m 
     * @return void 
     */
    static function process($m) {

        $fw = $m->framework;
        $pid = $fw->getProjectId();

        $m->includeCSS("css/setup.css");
        $m->includeCSS("css/print-widget.css");
        $m->includeCSS("css/3rd-party/datatables.min.css");

        $m->includeJS("js/3rd-party/dymo.connect.framework.js");
        $m->includeJS("js/3rd-party/bwip-js-min.js");
        $m->includeJS("js/3rd-party/datatables.min.js");
        $m->includeJS("js/3rd-party/autosize.min.js");
        $m->includeJS("js/3rd-party/bs-custom-file-input.min.js");
        $m->includeJS("js/dlem.js");

        // Ajax Setup.
        $crypto = Crypto::init($m);
        $ajax = array(
            "verification" => $crypto->encrypt(array(
                "random" => $crypto->genKey(),
                "userid" => $GLOBALS["userid"],
                "pid" => $pid,
                "timestamp" => time(),
            )),
            "endpoint" => $fw->getUrl("auth-ajax.php")
        );

        $labels = $m->getLabels();
        
        $dataTables_json = dirname(__FILE__) . "/" . $fw->tt("setup_datatables");
        $dataTables_json = is_file($dataTables_json) ? file_get_contents($dataTables_json) : "{}";

        // Prepare configuration data
        $configSettings = array(
            "debug" => $fw->getProjectSetting("js-debug") == true,
            "canDownload" => $fw->getProjectSetting("allow-download") == true,
            "ajax" => $ajax,
            "strings" => array (
                "chooseFile" => $fw->tt("setup_choosefile"),
                "nameRequired" => $fw->tt("setup_namerequired"),
                "actionInfo" => $fw->tt("setup_action_info"),
                "actionRename" => $fw->tt("setup_action_rename"),
                "actionConfigure" => $fw->tt("setup_action_configure"),
                "actionDownload" => $fw->tt("setup_action_download"),
                "actionPrint" => $fw->tt("setup_action_print"),
                "actionDelete" => $fw->tt("setup_action_delete"),
                "toastLabelUpdated" => $fw->tt("setup_toast_labelupdated"),
                "toastLabelAdded" => $fw->tt("setup_toast_labeladded"),
                "toastLabelRenamed" => $fw->tt("setup_toast_labelrenamed"),
                "toastLabelDeleted" => $fw->tt("setup_toast_labeldeleted"),
                "actionTagReplace" => $fw->tt("setup_info_actiontagreplace"),
                "widgetLabel" => $fw->tt("widget_label"),
                "transformT" => $fw->tt("setup_config_transform_text"),
                "transformR" => $fw->tt("setup_config_transform_remove"),
                "transformDM" => "Datamatrix",
                "transformQR" => "QR Code",
                "transformPNG" => "PNG",
                "invalidRange" => $fw->tt("error_invalidrange"),
                "clipboardError" => $fw->tt("error_clipboard"),
                "infoCopiedTag" => $fw->tt("setup_info_copiedtag"),
                "infoCopiedLink" => $fw->tt("setup_info_copiedlink"),
                "infoValue" => $fw->tt("setup_info_publicvalue"),
                "dataTablesLanguageJSON" => $dataTables_json, 
            ),
            "labels" => $labels,
            "linkBase" => $m->getUrl("public.php", true),
        );

        // Include mostly HTML
        include("setup-page.php");
        include("setup-modals.php");
        include("widget-modals.php");
        // Initiate JS with config data
        print "<script>$(function() { window.ExternalModules.DYMOLabelConfig_init(" . 
            json_encode($configSettings) . ") }); </script>";
    }
}
setupPluginPage::process($module);
