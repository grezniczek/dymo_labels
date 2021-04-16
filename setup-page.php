<?php 
    /**
     * DYMO Labels External Module
     * Setup page
     */
?>
<div class="dymo-labels-container">
    <div class="projhdr"><i class="fas fa-tags"></i> <?= $fw->tt("module_name")?></div>
    <p>
        <?=$fw->tt("setup_intro1")?>
        <a class="mb-2" style="text-decoration:underline;" href="javascript:;" onclick="$(this).remove();$('[data-dlem-learnmore]').addClass('d-md-block');"><?=$fw->tt("setup_learnmore")?></a>
    </p>
    <div data-dlem-learnmore class="d-none mb-2 mt-2">
        <p><?=$fw->tt("setup_intro2")?></p>
        <p><?=$fw->tt("setup_intro3")?></p>
        <p><?=$fw->tt("setup_intro4")?></p>
        <p><?=$fw->tt("setup_intro5")?></p>
    </div>
    <p class="mt-3 mb-3"><button type="button" data-command="add-new-label" class="btn btn-xs btn-rcgreen fs13"><i class="fa fa-plus"></i> <?=$fw->tt("setup_addnewlabel")?></button></p>
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
    if ($m->getProjectSetting("enable-post") == true && !($m->getSystemSetting("system-disable-post") == true)): 
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
