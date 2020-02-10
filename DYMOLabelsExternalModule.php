<?php

namespace RUB\DYMOLabelsExternalModule;

use ExternalModules\AbstractExternalModule;

/**
 * Provides an integration of DYMO LabelWriter printers with REDCap.
 */
class DYMOLabelsExternalModule extends AbstractExternalModule {

    const MANAGEMENT_LABEL_INSTRUMENT = "dymo_label";

    public static function read_label_file($edoc_id) {
        $contents = null;
        if ($edoc_id != null) {
            $filename = \Files::copyEdocToTemp($edoc_id, true);
            try {
                if (file_exists($filename)) {
                    $contents = file_get_contents($filename);
                    unlink($filename);
                }
            }
            catch (\Exception $e) {
            }
        }
        return $contents;
    }

    function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
        $fw = $this->framework;

        // Is the record being saved in the DYMO Label management project?
        $management_pid = $fw->getSystemSetting("system-management-project");
        if ($project_id != $management_pid || $instrument != self::MANAGEMENT_LABEL_INSTRUMENT) return;

        // Process the label file.
        // Get the record data.
        $fields = array ("record_id", "file");
        $rawdata = \REDCap::getData($project_id, "array", $record, $fields, $event_id);
        if (count($rawdata) < 1) return;
        
        // Get the file id.
        $edocId = $rawdata[$record][$event_id]["file"];
        if ($edocId == null) return;
        
        // Read and parse the file.
        $labelXml = self::read_label_file($edocId);
        $error = "";
        try {
            $doc = simplexml_load_string($labelXml);
        }
        catch (\Exception $e) {
            $error = "\nError: " . $e->getMessage();
        }
        if ($doc == false) {
            \REDCap::logEvent("Parse DYMO label", "FAILED to parse label file with edoc_id '{$edocId}'" . $error);
            return;
        }

        // Extract objects.
        $labelObjects = array(); 
        foreach ($doc->xpath("//ObjectInfo/TextObject") as $node) {
            $labelObject = array ();
            $labelObject["field_type"] = "txt";
            $labelObject["field_image"] = null;
            $labelObject["field_id"] = (string)$node->Name;
            $labelObject["field_default"] = (string)$node->StyledText->Element->String;
            $labelObjects[] = $labelObject;
        }
        foreach ($doc->xpath("//ObjectInfo/ImageObject") as $node) {
            $labelObject = array ();
            $labelObject["field_type"] = "img";
            $labelObject["field_id"] = (string)$node->Name;
            $labelObject["field_default"] = (string)$node->Image;
            $labelObjects[] = $labelObject;
        }

        // Get stored object data.
        $fields = array ("record_id", "obj_id", "obj_type", "obj_imggen", "obj_default");
        $rawdata = \REDCap::getData($project_id, "array", $record, $fields, $event_id, null, null, null, null, "[obj_id]<>''");

        // should get all fields for instrument with obj_name on it.
        // then, get all data for these fields (and probably log just to be sure not to lose anything)
        // add this data to all objects in labelObjects array.
        // then delete all from fields in this instrument from redcap_data (using sql)
        // finally, add new data.
    }


    /**
     * Control whether the plugin link is displayed.
     */
    function redcap_module_link_check_display($project_id, $link) {
        $fw = $this->framework;
        // Only show the link for the integrated plugin.
        // The 'project-showlink' setting shall not apply to super users.
        return ($link["id"] == "integrated" && $project_id != null && (SUPER_USER || $fw->getProjectSetting("project-showlink"))) ? $link : null;
    }


    /**
     * Set module default settings when the module is enabled.
     */
    function redcap_module_project_enable($version, $project_id) {
        $fw = $this->framework;
        // Set default configuration.
        if ($project_id == null) {
            // System.
            if ($fw->getSystemSetting("system-allow-anonymous") == null) {
                $fw->setSystemSetting("system-allow-anonymous", false);
            }
        }
        else {
            // Project 
            if ($fw->getProjectSetting("project-showlink") == null) {
                $fw->setProjectSetting("project-showlink", true, $project_id);
            }
        }
    }

}