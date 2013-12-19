<?php

function mcc_upgrade_105() {
	$model = mcc_get_model();
	$model->create_synced_posts_relationships_table();
}