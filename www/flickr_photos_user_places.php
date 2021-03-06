<?php

	include("include/init.php");

	loadlib("flickr_places");
	loadlib("flickr_photos_places");
	loadlib("flickr_photos_geo");

	if ((! $GLOBALS['cfg']['enable_feature_solr']) || (! $GLOBALS['cfg']['enable_feature_places'])){
		error_disabled();
	}

	$flickr_user = flickr_users_get_by_url();
	$owner = users_get_by_id($flickr_user['user_id']);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;

	$GLOBALS['smarty']->assign_by_ref("owner", $owner);
	$GLOBALS['smarty']->assign("is_own", $is_own);

	$facet = get_str("facet");

	$placetypes = flickr_places_valid_placetypes();

	if ((! $facet) || (! flickr_places_is_valid_placetype($facet))){
		$rand = rand(1, count($placetypes));
		$facet = $placetypes[$rand - 1];
	}

	$GLOBALS['smarty']->assign_by_ref("placetypes", $placetypes);
	$GLOBALS['smarty']->assign("facet", $facet);

	# TO DO: easter egg to make 'airports' a valid place type/url
	# to query by; this will probably mean indexing the place name
	# in solr which is probably not a bad idea anyway (20111229/straup)

	$more = array(
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
	);

	if ($context = get_str("context")){

		$map = flickr_photos_geo_context_map("string keys");

		if (isset($map[$context])){

			$geo_context = $map[$context];
			$more['geocontext'] = $geo_context;

			$GLOBALS['smarty']->assign("context", $context);
			$GLOBALS['smarty']->assign("geo_context", $geo_context);
		}
	}

	$rsp = flickr_photos_places_for_user_facet($owner, $facet, $more);

	if ($rsp['ok']){

		$locations = array();

		foreach ($rsp['facets'] as $woeid => $ignore){
			$loc = flickr_places_get_by_woeid($woeid);
			$locations[$woeid] = $loc;
		}

		$GLOBALS['smarty']->assign_by_ref("facets", $rsp['facets']);
		$GLOBALS['smarty']->assign_by_ref("locations", $locations);
	}

	else {

		$GLOBALS['smarty']->assign("error", $rsp['error']);
	}

	$GLOBALS['smarty']->display("page_flickr_photos_user_places.txt");
	exit();

?>
