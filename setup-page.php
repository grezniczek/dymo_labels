<?php 
    /**
     * DYMO Labels External Module
     * Setup page
     */
?>
<div class="dymo-labels-container">
    <h3><?= $fw->tt("module_name")?></h3>
    <p><?= $fw->tt("setup_intro")?></p>
    <p><button type="button" data-command="add-new-label" class="btn btn-xs btn-rcgreen fs13"><i class="fa fa-plus"></i> <?= $fw->tt("setup_addnewlabel")?></button></p>
    <table id="dlem-labels" class="table table-striped table-bordered table-hover" style="width:100%">
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Description</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<?php 
    if ($m->getProjectSetting("enable-post") == true && $m->getSystemSetting("system-enable-post") == true): 
?>
<p>
    <?= $fw->tt("setup_post") ?> <a href="javascript:$('.dlem-post-info').toggle();"><i class="far fa-question-circle"></i></a>
</p>
<div class="dlem-post-info" style="display:none;">
    <a target="_blank" href="<?=$fw->getUrl("post.php", true)?>"><?=$fw->getUrl("post.php", true)?></a>
</div>
<?php 
    endif; 
?>
