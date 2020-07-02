<?php namespace DE\RUB\DYMOLabelsExternalModule;

use Exception;

class ProjectStructure {

    public $ProjectName = "";
    public $Forms = array ();
    public $Fields = array ();

    /**
     * Assembles information about the project structure.
     * 
     * @param AbstractExternalModule $module A module instance.
     * @param int $project_id Project ID
     **/
    public function __construct($module, $project_id = null)
    {
        if ($module == null || !isset($module->framework)) {
            throw new Exception("Must be called with a module instance and at least framework version 2.");
        }
        // Get and verify project id.
        $project_id = $project_id ?? $module->framework->getProjectId();
        if (!$this->isValidProjectId($module, $project_id)) {
            throw new Exception("No or invalid project id supplied or project id could not be determined from context.");
        }
        // Get field and form names.
        $this->getFieldsAndForms($module, $project_id);



    }


    /**
     * @param AbstractExternalModule $module A module instance.
     */
    private function isValidProjectId($module, $project_id) {
        if ($project_id == null || !is_numeric($project_id)) return false;
        $project_id = 1 * $project_id;
        if (!is_int($project_id)) return false;
        /** @var \ExternalModules\StatementResult $result StatementResult */
        $result = $module->framework->query("SELECT app_title FROM redcap_projects WHERE project_id = ?", [$project_id]);
        if ($result->num_rows != 1) return false;
        $row = $result->fetch_assoc();
        $this->ProjectName = $row["app_title"];
        return true;
    }

    private function getFieldsAndForms($module, $project_id) {
        /** @var \ExternalModules\StatementResult $result StatementResult */
        $result = $module->framework->query("SELECT field_name, form_name, element_type FROM redcap_metadata WHERE project_id = ?", [$project_id]);
        
        while ($row = $result->fetch_assoc()) {
            $field = array ();
            $field["name"] = $row["field_name"];
            $field["type"] = $row["element_type"];
            $field["form"] = $row["form_name"];
            if (!isset($this->Forms[$field["form"]])) {
                $form = array (
                    "name" => $field["form"],
                    "fields" => array ()
                );
                $this->Forms[$field["form"]] = $form;
            }
            $this->Fields[$field["name"]] = $field;
            $this->Forms[$field["form"]]["fields"][$field["name"]] = $field;
        }
    }
}

